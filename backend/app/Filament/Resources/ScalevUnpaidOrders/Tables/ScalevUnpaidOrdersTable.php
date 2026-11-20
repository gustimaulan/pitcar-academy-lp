<?php

namespace App\Filament\Resources\ScalevUnpaidOrders\Tables;

use App\Models\ScalevUnpaidOrder;
use App\Support\WhatsAppNumber;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScalevUnpaidOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('ordered_at', 'desc')
            ->emptyStateHeading('Tidak ada pesanan Scalev yang menunggu')
            ->emptyStateDescription('Pesanan belum bayar dari '.ScalevUnpaidOrder::KEEP_DAYS.' hari terakhir muncul di sini setelah sync Scalev berikutnya (tiap jam).')
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Pembeli')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (ScalevUnpaidOrder $record) => $record->email),

                TextColumn::make('product_name')
                    ->label('Produk di Scalev')
                    ->limit(36)
                    ->description(fn (ScalevUnpaidOrder $record) => $record->course ? null : 'tidak cocok dengan kelas mana pun'),

                TextColumn::make('amount')
                    ->label('Nilai pesanan')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->placeholder('-'),

                TextColumn::make('ordered_at')
                    ->label('Dipesan')
                    ->dateTime('d M Y H:i')
                    ->since()
                    ->sortable(),

                TextColumn::make('followed_up_at')
                    ->label('Follow up')
                    ->dateTime('d M H:i')
                    ->description(fn (ScalevUnpaidOrder $record) => $record->followedUpBy?->name)
                    ->placeholder('Belum'),

                TextColumn::make('order_id')
                    ->label('No. Scalev')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('followed')
                    ->label('Follow up')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('followed_up_at'),
                        false: fn (Builder $query) => $query->whereNull('followed_up_at'),
                    ),
            ])
            ->recordActionsPosition(RecordActionsPosition::BeforeColumns)
            ->recordActions([self::followUp()]);
    }

    private static function followUp(): Action
    {
        return Action::make('followUp')
            ->label('Follow up')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->button()
            ->color(fn (ScalevUnpaidOrder $record) => $record->followed_up_at ? 'gray' : 'warning')
            ->modalHeading(fn (ScalevUnpaidOrder $record) => 'Follow up '.$record->customer_name)
            ->modalDescription(fn (ScalevUnpaidOrder $record) => $record->followed_up_at
                ? 'Terakhir di-follow up '.$record->followed_up_at->timezone(config('app.display_timezone'))->format('d M Y H:i').($record->followedUpBy ? ' oleh '.$record->followedUpBy->name : '').'.'
                : 'Periksa pesannya. Setelah disimpan, WhatsApp terbuka dengan pesan ini, tinggal tekan kirim.')
            ->modalSubmitActionLabel('Siapkan pesan')
            ->fillForm(fn (ScalevUnpaidOrder $record) => [
                'phone' => $record->whatsapp,
                'save_phone' => true,
                'message' => self::message($record),
            ])
            ->schema([
                TextInput::make('phone')
                    ->label('Nomor WhatsApp')
                    ->required()
                    ->tel()
                    ->rule(fn () => function (string $attribute, $value, $fail) {
                        if (WhatsAppNumber::normalize($value) === null) {
                            $fail('Nomor WhatsApp tidak valid. Contoh: 0812 3456 7890.');
                        }
                    }),
                Toggle::make('save_phone')
                    ->label('Simpan nomor ini')
                    ->default(true),
                Textarea::make('message')
                    ->label('Pesan')
                    ->required()
                    ->rows(9)
                    ->maxLength(1500),
            ])
            ->action(function (ScalevUnpaidOrder $record, array $data) {
                $phone = WhatsAppNumber::normalize($data['phone']);
                $record->update([
                    'followed_up_at' => now(),
                    'followed_up_by' => auth()->id(),
                    ...(($data['save_phone'] ?? false) ? ['whatsapp' => $phone] : []),
                ]);

                Notification::make()
                    ->success()
                    ->title('Pesan follow up siap')
                    ->body('Tekan "Buka WhatsApp", lalu kirim. Pesanan ini sudah ditandai sudah di-follow up.')
                    ->actions([
                        Action::make('open')
                            ->label('Buka WhatsApp')
                            ->button()
                            ->url('https://wa.me/'.$phone.'?text='.rawurlencode($data['message']), shouldOpenInNewTab: true),
                    ])
                    ->persistent()
                    ->send();
            });
    }

    /**
     * Points to the new checkout at today's price. The utm tags put the
     * replies that end in a sale under WhatsApp in the analytics page.
     */
    public static function message(ScalevUnpaidOrder $order): string
    {
        $first = strtok(trim($order->customer_name), ' ') ?: $order->customer_name;
        $utm = 'utm_source=whatsapp&utm_medium=follow-up&utm_campaign=scalev-pending';
        $base = rtrim((string) config('lms.frontend_url'), '/');
        $course = $order->course;

        if ($course === null) {
            return "Halo {$first}, ini dari Pitcar Academy. Kamu sempat memesan ".($order->product_name ?: 'kelas online kami').', tapi pembayarannya belum selesai. '
                ."Sekarang pemesanan kelas online pindah ke sini, dan materinya langsung terbuka setelah bayar:\n{$base}/kelas-online/?{$utm}\n\n"
                ."Tagihan yang lama tidak perlu dibayar.\nAda pertanyaan soal kelasnya? Balas pesan ini saja.";
        }

        $price = 'Rp'.number_format((int) $course->price, 0, ',', '.');

        return "Halo {$first}, ini dari Pitcar Academy. Kamu sempat memesan {$course->title}, tapi pembayarannya belum selesai. "
            ."Sekarang pemesanannya pindah ke sini, dan materinya langsung terbuka setelah bayar:\n{$base}/checkout/?kelas={$course->slug}&{$utm}\n\n"
            ."Harganya saat ini {$price}. Tagihan yang lama tidak perlu dibayar.\nAda pertanyaan soal kelasnya? Balas pesan ini saja.";
    }
}

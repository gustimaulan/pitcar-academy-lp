<?php

namespace App\Filament\Actions;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Services\Lms\InternalAccess;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * "Beri akses gratis": opens classes for someone in the team without an order.
 * Admins only, since it gives the product away.
 */
class GiveInternalAccess
{
    public static function make(?Course $course = null): Action
    {
        return Action::make('giveInternalAccess')
            ->label('Beri akses gratis')
            ->icon('heroicon-o-gift')
            ->color('gray')
            ->visible(fn () => auth()->user()?->isAdmin() ?? false)
            ->modalHeading('Beri akses kelas gratis untuk tim internal')
            ->modalDescription('Tidak membuat pesanan, jadi tidak masuk pendapatan dan analitik. Kalau email sudah terdaftar, kelas ditambahkan ke akun yang sama.')
            ->modalSubmitActionLabel('Buka akses')
            ->fillForm(['courses' => $course ? [$course->id] : [], 'send_email' => true])
            ->schema([
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                TextInput::make('name')->label('Nama')->required()->maxLength(255)
                    ->helperText('Akun yang sudah ada tetap memakai nama lamanya.'),
                TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(30),
                Select::make('courses')
                    ->label('Kelas')
                    ->multiple()
                    ->required()
                    ->options(fn () => Course::query()->orderBy('title')->get()
                        ->mapWithKeys(fn (Course $c) => [$c->id => $c->title.($c->status === CourseStatus::Draft ? ' (draft)' : '')])
                        ->all()),
                Toggle::make('send_email')
                    ->label('Kirim email berisi tautan masuk')
                    ->helperText(fn () => 'Tautan berlaku '.intdiv((int) config('lms.purchase_link_minutes'), 60).' jam. Siswa juga bisa minta tautan baru di halaman Masuk.'),
            ])
            ->action(function (array $data) {
                $result = app(InternalAccess::class)->give(
                    $data['name'],
                    $data['email'],
                    $data['whatsapp'] ?? null,
                    Course::query()->whereKey($data['courses'])->orderBy('title')->get(),
                    auth()->user(),
                    (bool) ($data['send_email'] ?? false),
                );

                $who = $result['student']->email;
                $body = collect([
                    $result['opened']->isNotEmpty() ? 'Dibuka: '.$result['opened']->pluck('title')->join(', ').'.' : null,
                    $result['had']->isNotEmpty() ? 'Sudah punya akses: '.$result['had']->pluck('title')->join(', ').'.' : null,
                    $result['opened']->isNotEmpty() && $data['send_email'] ? 'Email tautan masuk dikirim.' : null,
                ])->filter()->join(' ');

                Notification::make()
                    ->title($result['opened']->isNotEmpty() ? 'Akses gratis dibuka untuk '.$who : $who.' sudah punya semua kelas itu')
                    ->body($body)
                    ->status($result['opened']->isNotEmpty() ? 'success' : 'warning')
                    ->send();
            });
    }
}

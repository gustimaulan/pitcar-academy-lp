<?php

namespace App\Filament\Resources\CourseReviews\Tables;

use App\Models\CourseReview;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CourseReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Terakhir diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('course.title')
                    ->label('Kelas')
                    ->limit(32),

                TextColumn::make('student.name')
                    ->label('Siswa')
                    ->searchable()
                    ->description(fn (CourseReview $record) => $record->student->email),

                TextColumn::make('rating')
                    ->label('Nilai')
                    ->formatStateUsing(fn (int $state) => "{$state} / 5")
                    ->weight('bold')
                    // The low ones are the ones to read first.
                    ->color(fn (int $state) => $state <= 2 ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('body')
                    ->label('Ulasan')
                    ->placeholder('Tanpa ulasan')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('may_publish')
                    ->label('Boleh ditampilkan')
                    ->formatStateUsing(fn (bool $state) => $state ? 'Boleh' : 'Tidak')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray')
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label('Di halaman jualan')
                    ->state(fn (CourseReview $record) => match (true) {
                        $record->isPublished() => 'Tayang',
                        $record->canBePublished() => 'Belum',
                        default => 'Tidak bisa',
                    })
                    ->tooltip(fn (CourseReview $record) => $record->canBePublished()
                        ? null
                        : 'Hanya ulasan bertulisan yang siswanya mengizinkan bisa ditayangkan.')
                    ->color(fn (string $state) => match ($state) {
                        'Tayang' => 'success',
                        'Belum' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Kelas')
                    ->relationship('course', 'title'),

                SelectFilter::make('rating')
                    ->label('Nilai')
                    ->options([5 => '5', 4 => '4', 3 => '3', 2 => '2', 1 => '1']),

                TernaryFilter::make('may_publish')
                    ->label('Boleh ditampilkan'),

                TernaryFilter::make('published_at')
                    ->label('Di halaman jualan')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Belum tayang'),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Tayangkan')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (CourseReview $record) => $record->canBePublished() && ! $record->isPublished())
                    ->requiresConfirmation()
                    ->modalHeading('Tayangkan ulasan ini?')
                    ->modalDescription(fn (CourseReview $record) => 'Tampil di halaman jualan kelas dengan nama "'.$record->publicName().'" dan nilai '.$record->rating.'/5. Kalau siswa mengubah ulasannya, ulasan turun lagi sampai kamu tayangkan ulang.')
                    ->modalSubmitActionLabel('Tayangkan')
                    ->action(function (CourseReview $record) {
                        $record->update(['published_at' => now()]);
                        Notification::make()->success()->title('Ulasan tayang di halaman jualan')->send();
                    }),

                Action::make('unpublish')
                    ->label('Turunkan')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (CourseReview $record) => $record->isPublished())
                    ->action(function (CourseReview $record) {
                        $record->update(['published_at' => null]);
                        Notification::make()->success()->title('Ulasan diturunkan')->send();
                    }),
            ]);
    }
}

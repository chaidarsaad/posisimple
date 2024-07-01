<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $activeNavigationIcon = 'heroicon-s-user';

    protected static ?string $navigationGroup = 'Others';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->unique(ignoreRecord: true)
                    ->email()
                    ->required(),
                // Forms\Components\DateTimePicker::make('email_verified_at'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(8),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->options(function () {
                        $user = Auth::user();
                        $isOwner = $user->hasRole('Owner');
                        $isKasir = $user->hasRole('Kasir');

                        if ($isOwner || $isKasir) {
                            return Role::whereIn('name', ['Owner', 'Kasir'])->pluck('name', 'id');
                        }

                        // Default untuk semua role
                        return Role::pluck('name', 'id');
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                $isOwner = $user->hasRole('Owner');
                $isKasir = $user->hasRole('Kasir');

                if ($isOwner || $isKasir) {
                    $query->whereHas('roles', function ($query) {
                        $query->whereIn('name', ['Owner', 'Kasir']);
                    });
                }
            })

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                    Tables\Columns\TextColumn::make('roles.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $isOwner = Auth::user()->hasRole('Owner');
        $isKasir = Auth::user()->hasRole('Kasir');

        if ($isOwner) {
            $ownerCount = User::whereHas('roles', function ($query) {
                $query->where('name', 'Owner');
            })->count();

            $cashierCount = User::whereHas('roles', function ($query) {
                $query->where('name', 'Kasir');
            })->count();

            $count = $ownerCount + $cashierCount;

            return $count;
        } else if ($isKasir) {
            $ownerCount = User::whereHas('roles', function ($query) {
                $query->where('name', 'Owner');
            })->count();

            $cashierCount = User::whereHas('roles', function ($query) {
                $query->where('name', 'Kasir');
            })->count();

            $count = $ownerCount + $cashierCount;

            return $count;

            // return "Owners: $ownerCount, Cashiers: $cashierCount";
        } else {
            return static::getModel()::count();
        }
    }
}

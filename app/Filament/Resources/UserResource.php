<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord; // <-- ADDED: Needed for the conditional logic in the password field
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn; // <-- ADDED: Needed for table columns
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
// REMOVED: use Illuminate\Database\Eloquent\SoftDeletingScope; (Not used, but harmless)

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Use the Users icon
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255), // Good practice

                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),    

                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->default(now()),

                Forms\Components\TextInput::make('password')
                    ->password()
                    // If the field state is not empty, hash the password (dehydrate)
                    ->dehydrated(fn (?string $state): bool => filled($state)) 
                    // <-- FIXED: Use $operation to check if it's the 'create' page. 
                    ->required(fn (string $operation): bool => $operation === 'create')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('name')
                ->searchable(),
               Tables\Columns\TextColumn::make('email')
                ->searchable(),
               Tables\Columns\TextColumn::make('email_verified_at')
                ->dateTime()
                ->sortable(),
               Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),

                ])
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
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
<?php

namespace App\Filament\Resources\PartnerProducts\Pages;

use App\Actions\Products\ApproveProduct;
use App\Actions\Products\RecordPartnerWarehouseDeposit;
use App\Enums\ProductStatus;
use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use App\Models\Product;
use App\Support\UniqueSlug;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditPartnerProduct extends EditRecord
{
    protected static string $resource = PartnerProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['user_id'], $data['cargo_id']);
        $data['listed_in_shop'] = false;
        $data['slug'] = UniqueSlug::for(
            Product::class,
            (string) $data['name'],
            $this->record->id,
        );

        return $data;
    }

    protected function getHeaderActions(): array
    {
        /** @var Product $record */
        $record = $this->getRecord();

        return [
            Action::make('approve')
                ->label('Publier')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (): bool => $record->status === ProductStatus::PendingReview)
                ->requiresConfirmation()
                ->action(function () use ($record): void {
                    app(ApproveProduct::class)->handle($record->fresh());

                    Notification::make()
                        ->title('Produit partenaire publié')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            Action::make('recordDeposit')
                ->label('Enregistrer dépôt')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->visible(fn (): bool => $record->cargo_id !== null)
                ->form([
                    TextInput::make('quantity')
                        ->label('Quantité déposée')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->default(max(1, (int) $record->stock_quantity)),
                ])
                ->action(function (array $data) use ($record): void {
                    $admin = Auth::user();

                    if ($admin === null) {
                        return;
                    }

                    app(RecordPartnerWarehouseDeposit::class)->handle(
                        $record->fresh(),
                        $admin,
                        (int) $data['quantity'],
                    );

                    Notification::make()
                        ->title('Dépôt enregistré')
                        ->body('Le partenaire a été notifié.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'warehouse_deposited_at',
                        'warehouse_deposited_quantity',
                    ]);
                }),
            DeleteAction::make()->label('Supprimer'),
        ];
    }
}

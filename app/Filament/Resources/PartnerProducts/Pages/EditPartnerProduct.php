<?php

namespace App\Filament\Resources\PartnerProducts\Pages;

use App\Actions\Products\ApproveProduct;
use App\Enums\ProductStatus;
use App\Filament\Resources\PartnerProducts\PartnerProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPartnerProduct extends EditRecord
{
    protected static string $resource = PartnerProductResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['user_id']);

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
                ->action(function (ApproveProduct $approveProduct) use ($record): void {
                    $approveProduct->handle($record->fresh());

                    Notification::make()
                        ->title('Produit partenaire publié')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),
            DeleteAction::make()->label('Supprimer'),
        ];
    }
}

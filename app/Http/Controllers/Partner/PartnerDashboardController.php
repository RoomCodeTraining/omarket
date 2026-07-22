<?php

namespace App\Http\Controllers\Partner;

use App\Actions\Products\CreatePartnerProduct;
use App\Actions\Products\SubmitProductForReview;
use App\Actions\Products\UnpublishPartnerProduct;
use App\Actions\Products\UpdatePartnerProduct;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Partners\StorePartnerProductRequest;
use App\Http\Requests\Partners\UpdatePartnerProductRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Support\ProductImageChunkUpload;
use App\Support\ProductImageStorage;
use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PartnerDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $productsQuery = Product::query()->where('user_id', $user->id);

        $stats = [
            'total' => (clone $productsQuery)->count(),
            'draft' => (clone $productsQuery)->where('status', ProductStatus::Draft)->count(),
            'pending_review' => (clone $productsQuery)->where('status', ProductStatus::PendingReview)->count(),
            'published' => (clone $productsQuery)->where('status', ProductStatus::Published)->count(),
            'stock_units' => (int) (clone $productsQuery)->sum('stock_quantity'),
        ];

        $products = Product::query()
            ->with('category:id,name')
            ->where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category?->name,
                'price' => $product->priceFormatted(),
                'price_amount' => round($product->price_cents / 100, 2),
                'stock_quantity' => $product->stock_quantity,
                'unit' => $product->unit,
                'status' => $product->status->value,
                'status_label' => $product->status->label(),
                'image_url' => $product->imageUrl(),
            ]);

        $orders = Order::query()
            ->with(['items' => fn ($query) => $query->whereHas(
                'product',
                fn ($productQuery) => $productQuery->where('user_id', $user->id),
            )])
            ->whereHas(
                'items.product',
                fn ($query) => $query->where('user_id', $user->id),
            )
            ->latest('placed_at')
            ->limit(50)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status->value,
                'status_label' => $order->status->label(),
                'total' => $order->totalFormatted(),
                'placed_at' => $order->placed_at?->format('d/m/Y H:i'),
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'line_total' => number_format($item->line_total_cents / 100, 2, ',', ' ').' $',
                ])->values(),
            ]);

        return Inertia::render('Partner/Dashboard', [
            'partner' => [
                'name' => $user->name,
                'email' => $user->email,
                'can_publish' => $user->canPublishProducts(),
                'approved_at' => $user->partner_approved_at?->format('d/m/Y'),
            ],
            'stats' => $stats,
            'products' => $products,
            'orders' => $orders,
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->get(['id', 'name']),
            'defaults' => [
                'unit' => SiteSettings::defaultProductUnit(),
            ],
        ]);
    }

    public function storeProductImage(Request $request): JsonResponse
    {
        if ($request->hasFile('image')) {
            $request->validate([
                'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:8192'],
            ]);

            $path = $request->file('image')->store('products', 'public');
            $this->rememberUploadedImagePath($request, $path);

            return response()->json([
                'path' => $path,
                'url' => ProductImageStorage::url($path),
            ]);
        }

        $dataUrl = $request->input('image_base64');

        if (is_string($dataUrl) && $dataUrl !== '') {
            $comma = strpos($dataUrl, ',');
            $payload = $comma === false ? $dataUrl : substr($dataUrl, $comma + 1);
            $binary = base64_decode($payload, true);

            if ($binary === false || $binary === '') {
                return response()->json(['message' => 'Impossible de décoder l’image.'], 422);
            }
        } else {
            $binary = $request->getContent();
        }

        $path = ProductImageStorage::storeBinary($binary);

        $this->rememberUploadedImagePath($request, $path);

        return response()->json([
            'path' => $path,
            'url' => ProductImageStorage::url($path),
        ]);
    }

    public function storeProductImageChunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:255'],
            'total' => ['required', 'integer', 'min:1', 'max:256'],
            'chunk' => ['required', 'string', 'max:300000'],
        ]);

        $binary = base64_decode($validated['chunk'], true);

        if ($binary === false || $binary === '') {
            return response()->json(['message' => 'Impossible de décoder ce morceau.'], 422);
        }

        $result = ProductImageChunkUpload::storeChunk(
            $validated['upload_id'],
            (int) $validated['index'],
            (int) $validated['total'],
            $binary,
        );

        if ($result['complete'] === true) {
            $this->rememberUploadedImagePath($request, $result['path']);
        }

        return response()->json($result);
    }

    private function rememberUploadedImagePath(Request $request, string $path): void
    {
        $previous = $request->session()->get(ProductImageStorage::SESSION_KEY);
        if (is_string($previous) && $previous !== '' && Storage::disk('public')->exists($previous)) {
            Storage::disk('public')->delete($previous);
        }

        $request->session()->put(ProductImageStorage::SESSION_KEY, $path);
    }

    public function destroyProductImage(Request $request): JsonResponse
    {
        $path = $request->session()->pull(ProductImageStorage::SESSION_KEY);

        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['ok' => true]);
    }

    public function storeProduct(
        StorePartnerProductRequest $request,
        CreatePartnerProduct $action,
    ): RedirectResponse {
        $product = $action->handle($request->user(), $request->payload());

        return back()->with(
            'success',
            "Produit « {$product->name} » créé en brouillon.",
        );
    }

    public function updateProduct(
        UpdatePartnerProductRequest $request,
        Product $product,
        UpdatePartnerProduct $action,
    ): RedirectResponse {
        abort_unless($product->isOwnedBy($request->user()), 403);

        $product = $action->handle($product, $request->user(), $request->payload());

        return back()->with(
            'success',
            "Produit « {$product->name} » mis à jour.",
        );
    }

    public function submitProduct(
        Request $request,
        Product $product,
        SubmitProductForReview $action,
    ): RedirectResponse {
        abort_unless($product->isOwnedBy($request->user()), 403);

        $action->handle($product, $request->user());

        return back()->with(
            'success',
            "« {$product->name} » soumis pour validation Ôhéfê.",
        );
    }

    public function unpublishProduct(
        Request $request,
        Product $product,
        UnpublishPartnerProduct $action,
    ): RedirectResponse {
        abort_unless($product->isOwnedBy($request->user()), 403);

        $action->handle($product, $request->user());

        return back()->with(
            'success',
            "« {$product->name} » retiré de la boutique.",
        );
    }
}

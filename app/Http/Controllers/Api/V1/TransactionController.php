<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Resources\PaginatedResource;
use App\Http\Resources\TransactionResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetTransactionsRequest $request)
    {
        $transactions = Transaction::with(['customer', 'items.product'])
            ->search($request->search)
            ->latest()
            ->paginate($request->limit ?? 10);

        return ApiResponse::success(
            new PaginatedResource($transactions, TransactionResource::class),
            'Transactions list'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            $transaction = DB::transaction(function () use ($request) {
                $validated = $request->validated();
                
                $items = $validated['items'];
                $taxAmount = $validated['tax'] ?? 0;
                $subtotal = 0;
                
                $transactionItemsData = [];

                foreach ($items as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    
                    if (!$product) {
                        throw new Exception("Product with ID {$item['product_id']} not found");
                    }
                    
                    if ($product->stock < $item['quantity']) {
                        throw new Exception("Insufficient stock for product: {$product->name}");
                    }
                    
                    $product->decrement('stock', $item['quantity']);
                    
                    $itemSubtotal = $product->price * $item['quantity'];
                    $subtotal += $itemSubtotal;
                    
                    $transactionItemsData[] = [
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'quantity' => $item['quantity'],
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $total = $subtotal + $taxAmount;
                
                // Generate a simple code e.g. TRX-20230907-XXXX
                $code = 'TRX-' . date('Ymd') . '-' . strtoupper(uniqid());

                $transaction = Transaction::create([
                    'code' => $code,
                    'customer_id' => $validated['customer_id'] ?? null,
                    'subtotal' => $subtotal,
                    'tax' => $taxAmount,
                    'total' => $total,
                ]);

                foreach ($transactionItemsData as $data) {
                    $transaction->items()->create($data);
                }

                return $transaction;
            });

            return ApiResponse::success(
                new TransactionResource($transaction->load(['customer', 'items.product'])),
                'Transaction created successfully',
                Response::HTTP_CREATED
            );

        } catch (Exception $e) {
            return ApiResponse::error(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::with(['customer', 'items.product'])->find($id);

        if (!$transaction) {
            return ApiResponse::error(
                'Transaction not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new TransactionResource($transaction),
            'Transaction details'
        );
    }
}

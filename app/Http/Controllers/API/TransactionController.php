<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.service'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada '
                );
            }
        }

        $transaction = Transaction::with(['items.service'])->where('users_id', Auth::user()->id);

        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list transaksi berhasil diambil',
        );
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:services,id',
            'total_price' => 'required',
            'extra_price' => 'required',
            'status' => 'required|in:PENDING,SUCCESS,CANCELLED,FAILED'
        ]);

        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'extra_price' => $request->extra_price,
            'status' => $request->status

        ]);

        foreach ($request->items as $service) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'services_id' => $service['id'],
                'transactions_id' => $transaction->id,
                'quantity' => $service['quantity']
            ]);
        }
        return ResponseFormatter::success($transaction->load('items.service'), 'Transaksi berhasil');
    }
}

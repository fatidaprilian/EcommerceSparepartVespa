<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::user()->orders()
            ->with(['items.product', 'address'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Order $order)
    {
        // Otorisasi: Hanya pemilik pesanan atau admin yang boleh lihat
        if ($order->user_id !== Auth::id() && !Auth::user()->hasRole('Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['items.product', 'address']);

        return Inertia::render('Orders/Show', [
            'order' => $order,
            // Variabel untuk mengontrol tampilan tombol di frontend
            'canUploadProof' => Auth::user()->hasRole('Reseller') && $order->payment_status === 'pending_payment',
            'canVerifyPayment' => Auth::user()->hasRole('Admin') && $order->payment_status === 'pending_verification',
        ]);
    }

    public function uploadProof(Request $request, Order $order)
    {
        // Otorisasi: Hanya reseller pemilik pesanan yang boleh upload
        if ($order->user_id !== Auth::id() || !Auth::user()->hasRole('Reseller')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:100',
            'transfer_date' => 'required|date',
        ]);

        // Simpan file ke disk 'public' agar bisa diakses via URL
        $proofPath = $request->file('proof')->store('payment-proofs', 'public');

        $order->update([
            'payment_proof' => $proofPath,
            'payment_status' => 'pending_verification',
            'payment_metadata' => json_encode([
                'bank_name' => $request->bank_name,
                'account_name' => $request->account_name,
                'transfer_date' => $request->transfer_date,
            ])
        ]);

        return redirect()->back()->with('success', 'Bukti transfer berhasil diupload!');
    }

    public function verifyPayment(Request $request, Order $order)
    {
        // Otorisasi: Hanya admin yang boleh verifikasi
        if (!Auth::user()->hasRole('Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        $metadata = json_decode($order->payment_metadata, true) ?? [];

        if ($request->action === 'approve') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'paid_at' => now(),
                'payment_metadata' => json_encode(array_merge($metadata, [
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                    'notes' => $request->notes,
                ]))
            ]);
        } else { // Reject
            $order->update([
                'payment_status' => 'payment_rejected',
                'payment_metadata' => json_encode(array_merge($metadata, [
                    'rejection_reason' => $request->notes,
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]))
            ]);
        }

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui!');
    }
}

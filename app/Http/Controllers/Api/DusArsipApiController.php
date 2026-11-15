<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DusArsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DusArsipApiController extends Controller
{
    /**
     * Cari & ambil data detail dus arsip beserta daftar berkasnya
     * Menerima kode_qrcode, ID dus, atau URL lengkap hasil scan QR.
     */
    public function show(string $kode): JsonResponse
    {
        $dusArsipId = $this->resolveDusArsipId($kode);

        if (! $dusArsipId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Box / Dus Arsip tidak ditemukan. Periksa kembali kode atau QR yang discan.',
            ], 404);
        }

        $dusArsip = DusArsip::with(['rakArsip', 'opd.opdInduk'])
            ->find($dusArsipId);

        if (! $dusArsip) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data Dus Arsip tidak ditemukan.',
            ], 404);
        }

        $arsips = $dusArsip->arsips()
            ->with(['masterKode', 'periode'])
            ->orderBy('nomor')
            ->orderBy('id')
            ->get()
            ->map(function ($arsip) {
                return [
                    'id' => $arsip->id,
                    'nomor' => $arsip->nomor ?: '-',
                    'nomor_sementara' => $arsip->nomor_sementara ?: '-',
                    'judul' => $arsip->judul,
                    'deskripsi' => $arsip->deskripsi ?: '',
                    'kode_klasifikasi' => $arsip->masterKode ? $arsip->masterKode->kode : '-',
                    'nama_klasifikasi' => $arsip->masterKode ? $arsip->masterKode->nama : '-',
                    'tanggal' => $arsip->tanggal ? $arsip->tanggal->format('d M Y') : '-',
                    'tahun' => $arsip->tahun ?: ($arsip->tanggal ? $arsip->tanggal->format('Y') : '-'),
                    'tanggal_musnah' => $arsip->tanggal_musnah ? $arsip->tanggal_musnah->format('d M Y') : '-',
                    'is_expired' => $arsip->isExpired(),
                    'retensi_aktif' => $arsip->aktif,
                    'retensi_inaktif' => $arsip->inaktif,
                    'status' => $arsip->status?->getLabel() ?? $arsip->status?->value ?? '-',
                    'nasib_akhir' => $arsip->nasib_akhir?->getLabel() ?? $arsip->nasib_akhir?->value ?? '-',
                    'keterangan_fisik' => $arsip->keterangan?->getLabel() ?? $arsip->keterangan?->value ?? '-',
                    'jumlah_isi_berkas' => $arsip->jumlah_isi_berkas ?: 1,
                    'file_url' => $arsip->file ? asset('storage/' . $arsip->file) : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'dus' => [
                    'id' => $dusArsip->id,
                    'nomor_dus' => $dusArsip->nomor_dus ?: (string) $dusArsip->id,
                    'kode_qrcode' => $dusArsip->kode_qrcode,
                    'nomor_rak' => $dusArsip->rakArsip?->nomor_rak ?: '-',
                    'dinas' => $dusArsip->opd?->opdInduk?->instansi ?: '-',
                    'unit_kerja' => $dusArsip->opd?->unit_kerja ?: '-',
                    'total_arsip' => $arsips->count(),
                    'tahun' => $dusArsip->created_at ? $dusArsip->created_at->format('Y') : date('Y'),
                    'qrcode_image_url' => route('dus-arsip.qrcode', $dusArsip),
                ],
                'arsips' => $arsips,
            ],
        ]);
    }

    /**
     * Resolusi input scanner menjadi ID Dus
     */
    protected function resolveDusArsipId(string $kode): ?int
    {
        $kode = trim($kode);

        // Jika berbentuk URL (misal scan QR versi web URL)
        if (preg_match('#/dus/(\d+)/rangkuman#', $kode, $matches)) {
            return (int) $matches[1];
        }

        // Jika berupa kode_qrcode (misal: "3-12-20260915")
        $dus = DusArsip::where('kode_qrcode', $kode)->first();
        if ($dus) {
            return $dus->id;
        }

        // Jika berupa ID angka polos
        if (ctype_digit($kode) && DusArsip::whereKey($kode)->exists()) {
            return (int) $kode;
        }

        return null;
    }
}

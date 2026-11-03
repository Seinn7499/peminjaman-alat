<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;
use Throwable;

class PeminjamanController extends Controller
{
    private const PESAN_AMAN = 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.';

    private function pdo(): PDO
    {
        $pdo = DB::connection()->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    private function bersihkan(Request $r): array
    {
        $d = [];
        foreach (['kode_peminjaman', 'nama_peminjam', 'nomor_identitas', 'jumlah_alat', 'alat_id'] as $k) {
            $d[$k] = trim(strip_tags((string) $r->input($k, '')));
        }
        return $d;
    }

    private function sudahAda(PDO $pdo, string $kolom, string $nilai, ?int $kecualiId): bool
    {
        $sql = match ($kolom) {
            'kode_peminjaman' => 'SELECT COUNT(*) FROM peminjaman_alat WHERE kode_peminjaman = :nilai',
            'nomor_identitas' => 'SELECT COUNT(*) FROM peminjaman_alat WHERE nomor_identitas = :nilai',
        };
        $params = [':nilai' => $nilai];
        if ($kecualiId !== null) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $kecualiId;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return (int) $st->fetchColumn() > 0;
    }

    private function validasi(array $d, PDO $pdo, ?int $kecualiId = null): array
    {
        $v = Validator::make($d, [
            'kode_peminjaman' => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
            'nama_peminjam'   => ['required', 'min:3', 'max:100'],
            'nomor_identitas' => ['required', 'min:5', 'max:30'],
            'jumlah_alat'     => ['required', 'integer', 'between:1,10'],
            'alat_id'         => ['required', 'integer'],
        ], [
            'required' => ':attribute wajib diisi.',
            'regex'    => ':attribute harus tepat 10 karakter alfanumerik.',
            'min'      => ':attribute minimal :min karakter.',
            'max'      => ':attribute maksimal :max karakter.',
            'integer'  => ':attribute harus berupa bilangan bulat.',
            'between'  => ':attribute harus antara :min sampai :max.',
        ], [
            'kode_peminjaman' => 'Kode peminjaman',
            'nama_peminjam'   => 'Nama peminjam',
            'nomor_identitas' => 'Nomor identitas',
            'jumlah_alat'     => 'Jumlah alat',
            'alat_id'         => 'Alat',
        ]);

        $err = $v->errors()->toArray();

        if (!isset($err['kode_peminjaman']) && $this->sudahAda($pdo, 'kode_peminjaman', $d['kode_peminjaman'], $kecualiId)) {
            $err['kode_peminjaman'][] = 'Kode peminjaman sudah digunakan.';
        }
        if (!isset($err['nomor_identitas']) && $this->sudahAda($pdo, 'nomor_identitas', $d['nomor_identitas'], $kecualiId)) {
            $err['nomor_identitas'][] = 'Nomor identitas sudah digunakan.';
        }
        if (!isset($err['alat_id'])) {
            $st = $pdo->prepare('SELECT COUNT(*) FROM alat WHERE id = :id');
            $st->execute([':id' => (int) $d['alat_id']]);
            if ((int) $st->fetchColumn() === 0) {
                $err['alat_id'][] = 'Alat yang dipilih tidak tersedia.';
            }
        }
        return $err;
    }

    public function index(Request $request)
    {
        $q      = trim(strip_tags((string) $request->query('q', '')));
        $alatId = trim((string) $request->query('alat_id', ''));
        $data   = [];
        $alat   = [];
        $galat  = null;

        try {
            $pdo  = $this->pdo();
            $alat = $pdo->query('SELECT id, nama_alat FROM alat ORDER BY nama_alat')->fetchAll(PDO::FETCH_ASSOC);

            $sql = 'SELECT p.id, p.kode_peminjaman, p.nama_peminjam, p.nomor_identitas, p.jumlah_alat, a.nama_alat
                    FROM peminjaman_alat p
                    JOIN alat a ON a.id = p.alat_id
                    WHERE 1 = 1';
            $params = [];

            if ($q !== '') {
                $sql .= ' AND (p.kode_peminjaman LIKE :q1 OR p.nama_peminjam LIKE :q2 OR p.nomor_identitas LIKE :q3)';
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
            }
            if ($alatId !== '' && ctype_digit($alatId)) {
                $sql .= ' AND p.alat_id = :alat_id';
                $params[':alat_id'] = (int) $alatId;
            }
            $sql .= ' ORDER BY p.id';

            $st = $pdo->prepare($sql);
            $st->execute($params);
            $data = $st->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            report($e);
            $galat = self::PESAN_AMAN;
        }

        return view('peminjaman.index', compact('data', 'alat', 'q', 'alatId', 'galat'));
    }

    public function store(Request $request)
    {
        $d = $this->bersihkan($request);

        try {
            $pdo = $this->pdo();
            $err = $this->validasi($d, $pdo);
            if ($err) {
                return redirect('/peminjaman')->withErrors($err)->withInput($d);
            }

            $st = $pdo->prepare(
                'INSERT INTO peminjaman_alat (kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id)
                 VALUES (:kode, :nama, :no, :jml, :alat)'
            );
            $st->execute([
                ':kode' => $d['kode_peminjaman'],
                ':nama' => $d['nama_peminjam'],
                ':no'   => $d['nomor_identitas'],
                ':jml'  => (int) $d['jumlah_alat'],
                ':alat' => (int) $d['alat_id'],
            ]);

            return redirect('/peminjaman')->with('success', 'Data peminjaman berhasil ditambahkan.');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return redirect('/peminjaman')
                    ->withErrors(['kode_peminjaman' => 'Kode atau nomor identitas sudah digunakan.'])
                    ->withInput($d);
            }
            report($e);
            return redirect('/peminjaman')->with('error', self::PESAN_AMAN)->withInput($d);
        } catch (Throwable $e) {
            report($e);
            return redirect('/peminjaman')->with('error', self::PESAN_AMAN)->withInput($d);
        }
    }

    public function edit(int $id)
    {
        try {
            $pdo = $this->pdo();
            $st = $pdo->prepare(
                'SELECT id, kode_peminjaman, nama_peminjam, nomor_identitas, jumlah_alat, alat_id
                 FROM peminjaman_alat WHERE id = :id'
            );
            $st->execute([':id' => $id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return redirect('/peminjaman')->with('error', 'Data tidak ditemukan.');
            }
            $alat = $pdo->query('SELECT id, nama_alat FROM alat ORDER BY nama_alat')->fetchAll(PDO::FETCH_ASSOC);

            return view('peminjaman.edit', compact('row', 'alat'));
        } catch (Throwable $e) {
            report($e);
            return redirect('/peminjaman')->with('error', self::PESAN_AMAN);
        }
    }

    public function update(Request $request, int $id)
    {
        $d = $this->bersihkan($request);

        try {
            $pdo = $this->pdo();

            $cek = $pdo->prepare('SELECT COUNT(*) FROM peminjaman_alat WHERE id = :id');
            $cek->execute([':id' => $id]);
            if ((int) $cek->fetchColumn() === 0) {
                return redirect('/peminjaman')->with('error', 'Data tidak ditemukan.');
            }

            $err = $this->validasi($d, $pdo, $id);
            if ($err) {
                return redirect("/peminjaman/{$id}/edit")->withErrors($err)->withInput($d);
            }

            $st = $pdo->prepare(
                'UPDATE peminjaman_alat
                 SET kode_peminjaman = :kode, nama_peminjam = :nama, nomor_identitas = :no,
                     jumlah_alat = :jml, alat_id = :alat
                 WHERE id = :id'
            );
            $st->execute([
                ':kode' => $d['kode_peminjaman'],
                ':nama' => $d['nama_peminjam'],
                ':no'   => $d['nomor_identitas'],
                ':jml'  => (int) $d['jumlah_alat'],
                ':alat' => (int) $d['alat_id'],
                ':id'   => $id,
            ]);

            return redirect('/peminjaman')->with('success', 'Data peminjaman berhasil diperbarui.');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return redirect("/peminjaman/{$id}/edit")
                    ->withErrors(['kode_peminjaman' => 'Kode atau nomor identitas sudah digunakan.'])
                    ->withInput($d);
            }
            report($e);
            return redirect("/peminjaman/{$id}/edit")->with('error', self::PESAN_AMAN)->withInput($d);
        } catch (Throwable $e) {
            report($e);
            return redirect("/peminjaman/{$id}/edit")->with('error', self::PESAN_AMAN)->withInput($d);
        }
    }

    public function destroy(int $id)
    {
        try {
            $st = $this->pdo()->prepare('DELETE FROM peminjaman_alat WHERE id = :id');
            $st->execute([':id' => $id]);

            if ($st->rowCount() === 0) {
                return redirect('/peminjaman')->with('error', 'Data tidak ditemukan.');
            }
            return redirect('/peminjaman')->with('success', 'Data peminjaman berhasil dihapus.');
        } catch (Throwable $e) {
            report($e);
            return redirect('/peminjaman')->with('error', self::PESAN_AMAN);
        }
    }
}
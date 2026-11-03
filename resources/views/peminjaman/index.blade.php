<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Peminjaman Alat</title>
</head>
<body>
    <h1>Sistem Peminjaman Alat</h1>

    @if (session('success'))
        <p><strong>{{ session('success') }}</strong></p>
    @endif
    @if (session('error'))
        <p><strong>{{ session('error') }}</strong></p>
    @endif
    @if ($galat)
        <p><strong>{{ $galat }}</strong></p>
    @endif
    @if ($errors->any())
        <p>Terdapat kesalahan input:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Tambah Peminjaman</h2>
    <form method="POST" action="/peminjaman">
        @csrf
        <p>
            <label>Kode Peminjaman (10 karakter)<br>
            <input type="text" name="kode_peminjaman" value="{{ old('kode_peminjaman') }}"></label>
        </p>
        <p>
            <label>Nama Peminjam<br>
            <input type="text" name="nama_peminjam" value="{{ old('nama_peminjam') }}"></label>
        </p>
        <p>
            <label>Nomor Identitas<br>
            <input type="text" name="nomor_identitas" value="{{ old('nomor_identitas') }}"></label>
        </p>
        <p>
            <label>Jumlah Alat (1-10)<br>
            <input type="text" name="jumlah_alat" value="{{ old('jumlah_alat') }}"></label>
        </p>
        <p>
            <label>Alat<br>
            <select name="alat_id">
                <option value="">-- Pilih alat --</option>
                @foreach ($alat as $a)
                    <option value="{{ $a['id'] }}" @if (old('alat_id') == $a['id']) selected @endif>{{ $a['nama_alat'] }}</option>
                @endforeach
            </select></label>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <h2>Cari dan Filter</h2>
    <form method="GET" action="/peminjaman">
        <label>Kata kunci (kode, nama, no. identitas)
            <input type="text" name="q" value="{{ $q }}"></label>
        <label>Alat
            <select name="alat_id">
                <option value="">Semua alat</option>
                @foreach ($alat as $a)
                    <option value="{{ $a['id'] }}" @if ($alatId == $a['id']) selected @endif>{{ $a['nama_alat'] }}</option>
                @endforeach
            </select></label>
        <button type="submit">Terapkan</button>
        <a href="/peminjaman">Reset</a>
    </form>

    <h2>Daftar Peminjaman (master: tabel alat)</h2>
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Nama Peminjam</th>
                <th>No. Identitas</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Alat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['kode_peminjaman'] }}</td>
                    <td>{{ $row['nama_peminjam'] }}</td>
                    <td>{{ $row['nomor_identitas'] }}</td>
                    <td>{{ $row['jumlah_alat'] }}</td>
                    <td>
                        @if (statusJumlah((int) $row['jumlah_alat']) === 'Banyak')
                            Banyak
                        @else
                            Sedikit
                        @endif
                    </td>
                    <td>{{ $row['nama_alat'] }}</td>
                    <td>
                        <a href="/peminjaman/{{ $row['id'] }}/edit">Edit</a>
                        <form method="POST" action="/peminjaman/{{ $row['id'] }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Data tidak ditemukan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
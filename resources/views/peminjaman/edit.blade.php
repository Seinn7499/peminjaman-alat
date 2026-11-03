<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Peminjaman</title>
</head>
<body>
    <h1>Edit Peminjaman</h1>

    @if (session('error'))
        <p><strong>{{ session('error') }}</strong></p>
    @endif
    @if ($errors->any())
        <p>Terdapat kesalahan input:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="/peminjaman/{{ $row['id'] }}">
        @csrf
        @method('PUT')
        <p>
            <label>Kode Peminjaman (10 karakter)<br>
            <input type="text" name="kode_peminjaman" value="{{ old('kode_peminjaman', $row['kode_peminjaman']) }}"></label>
        </p>
        <p>
            <label>Nama Peminjam<br>
            <input type="text" name="nama_peminjam" value="{{ old('nama_peminjam', $row['nama_peminjam']) }}"></label>
        </p>
        <p>
            <label>Nomor Identitas<br>
            <input type="text" name="nomor_identitas" value="{{ old('nomor_identitas', $row['nomor_identitas']) }}"></label>
        </p>
        <p>
            <label>Jumlah Alat (1-10)<br>
            <input type="text" name="jumlah_alat" value="{{ old('jumlah_alat', $row['jumlah_alat']) }}"></label>
        </p>
        <p>
            <label>Alat<br>
            <select name="alat_id">
                @foreach ($alat as $a)
                    <option value="{{ $a['id'] }}" @if (old('alat_id', $row['alat_id']) == $a['id']) selected @endif>{{ $a['nama_alat'] }}</option>
                @endforeach
            </select></label>
        </p>
        <button type="submit">Simpan Perubahan</button>
        <a href="/peminjaman">Batal</a>
    </form>
</body>
</html>
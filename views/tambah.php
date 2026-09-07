<div class="card">
    <h2>Tambah Data Mahasiswa Magang</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_internship">
        <div class="grid">
            <div><label>NIM</label><input name="nim" required></div>
            <div><label>Nama</label><input name="nama" required></div>
            <div><label>Program Studi</label><input name="prodi" required></div>
            <div><label>Perusahaan Magang</label><input name="perusahaan" required></div>
            <div><label>Dosen Pembimbing</label><input name="dosen_pembimbing" required></div>
            <div><label>Tanggal Mulai Magang</label><input type="date" name="tanggal_mulai" required></div>
        </div>
        <p style="margin-top:12px;"><button type="submit">Simpan</button></p>
    </form>
</div>
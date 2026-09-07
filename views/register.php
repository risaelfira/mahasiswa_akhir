<div class="card">
    <h2>Registrasi Mahasiswa Magang</h2>
    <p class="muted">Biodata diisi sekali saat registrasi.</p>
    <form method="post">
        <input type="hidden" name="action" value="register">
        <div class="grid">
            <div><label>Username</label><input name="username" required></div>
            <div><label>Email</label><input type="email" name="email" required></div>
            <div><label>Password</label><input type="password" name="password" required></div>
            <div><label>Konfirmasi Password</label><input type="password" name="confirm_password" required></div>

            <div><label>NIM</label><input name="nim" required></div>
            <div><label>Nama</label><input name="nama" required></div>
            <div><label>Program Studi</label><input name="prodi" required></div>
            <div><label>Perusahaan Magang</label><input name="perusahaan" required></div>
            <div><label>Dosen Pembimbing</label><input name="dosen_pembimbing" required></div>
            <div><label>Tanggal Mulai Magang</label><input type="date" name="tanggal_mulai" required></div>
        </div>
        <p style="margin-top:12px;"><button type="submit">Daftar & Masuk</button></p>
    </form>
</div>
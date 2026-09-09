<div class="card">
    <h2>Menu To Do List</h2>
    <p class="muted">Tambahkan tugas, deadline, dan tandai saat sudah selesai.</p>
</div>

<div class="card note-form-card">
    <h3>Tambah Tugas</h3>
    <form method="post">
        <input type="hidden" name="action" value="save_note">
        <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? 0) ?>">

        <div class="grid">
            <div>
                <label>Tanggal Target</label>
                <input type="date" name="note_date" required>
            </div>
            <div>
                <label>Prioritas</label>
                <select name="note_priority">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div style="grid-column:1 / -1;">
                <label>Nama Tugas</label>
                <textarea name="note_text" required placeholder="Contoh: Membuat report selama 1 minggu"></textarea>
            </div>
        </div>

        <div style="margin-top:14px;">
            <button type="submit">Simpan Tugas</button>
        </div>
    </form>
</div>

<?php
$pendingNotes = [];
$doneNotes = [];

foreach (($notes ?? []) as $note) {
    if ((int)($note['note_done'] ?? 0) === 1) {
        $doneNotes[] = $note;
    } else {
        $pendingNotes[] = $note;
    }
}
?>

<div class="card">
    <h3>To Do List</h3>
    <div class="table-wrap">
        <table class="todo-table">
            <thead>
                <tr>
                    <th style="width:110px;">Status</th>
                    <th style="width:150px;">Tanggal</th>
                    <th>Tugas</th>
                    <th style="width:140px;">Prioritas</th>
                    <th style="width:110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($pendingNotes)): ?>
                <tr>
                    <td colspan="5" class="muted">Tidak ada tugas aktif.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($pendingNotes as $note): ?>
                <tr>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="toggle_note">
                            <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? 0) ?>">
                            <input type="hidden" name="note_id" value="<?= (int)$note['id'] ?>">
                            <input type="hidden" name="note_done" value="<?= (int)($note['note_done'] ?? 0) ?>">

                            <label class="check-box">
                                <input type="checkbox" onchange="this.form.submit()">
                                <span>Selesai</span>
                            </label>
                        </form>
                    </td>
                    <td><?= e(date('d M Y', strtotime((string)($note['note_date'] ?? '')))) ?></td>
                    <td><?= nl2br(e((string)($note['note_text'] ?? ''))) ?></td>
                    <td><?= e((string)($note['note_priority'] ?? 'normal')) ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Hapus tugas ini?');">
                            <input type="hidden" name="action" value="delete_note">
                            <input type="hidden" name="internship_id" value="<?= (int)($id ?? $internship_id ?? 0) ?>">
                            <input type="hidden" name="note_id" value="<?= (int)$note['id'] ?>">
                            <button type="submit" class="btn-plain">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h3>Tugas Selesai</h3>
    <div class="table-wrap">
        <table class="todo-table">
            <thead>
                <tr>
                    <th style="width:150px;">Tanggal</th>
                    <th>Tugas</th>
                    <th style="width:140px;">Prioritas</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($doneNotes)): ?>
                <tr>
                    <td colspan="3" class="muted">Belum ada tugas selesai.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($doneNotes as $note): ?>
                <tr class="todo-done">
                    <td><?= e(date('d M Y', strtotime((string)($note['note_date'] ?? '')))) ?></td>
                    <td><?= nl2br(e((string)($note['note_text'] ?? ''))) ?></td>
                    <td><?= e((string)($note['note_priority'] ?? 'normal')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<h4>Tambah PIC Baru</h4>
<form id="new-pic-form" action="<?= site_url('master/custpic/store') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="cust_pic_cust_uuid" value="<?= esc($cust_uuid) ?>">
    
    <div class="mb-2">
        <label>Panggilan (Bpk/Ibu)</label>
        <input type="text" name="cust_pic_panggilan" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Nama Kontak</label>
        <input type="text" name="cust_pic_kontak" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Jabatan</label>
        <input type="text" name="cust_pic_position" class="form-control">
    </div>
    <div class="mb-2">
        <label>Telepon</label>
        <input type="text" name="cust_pic_phone1" class="form-control">
    </div>
    
    <button type="submit" class="btn btn-primary mt-3">Simpan PIC</button>
</form>
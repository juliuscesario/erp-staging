<h4>Edit Data PIC</h4>
<form action="<?= site_url('master/custpic/update/' . $pic['cust_pic_uuid']) ?>" method="post">
    <?= csrf_field() ?>
    
    <div class="mb-2">
        <label>Panggilan (Bpk/Ibu)</label>
        <input type="text" name="cust_pic_panggilan" class="form-control" value="<?= esc($pic['cust_pic_panggilan']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Nama Kontak</label>
        <input type="text" name="cust_pic_kontak" class="form-control" value="<?= esc($pic['cust_pic_kontak']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Jabatan</label>
        <input type="text" name="cust_pic_position" class="form-control" value="<?= esc($pic['cust_pic_position']) ?>">
    </div>
    <div class="mb-2">
        <label>Telepon</label>
        <input type="text" name="cust_pic_phone1" class="form-control" value="<?= esc($pic['cust_pic_phone1']) ?>">
    </div>
    
    <button type="submit" class="btn btn-primary mt-3">Update PIC</button>
</form>
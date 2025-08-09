<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cetak Stiker QR</title>
    <style>
        /* Hilangkan margin dan padding */
        @page { margin: 0; }
        body { margin: 0; padding: 20px; text-align: center; font-family: sans-serif; }
        .sticker-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .qr-code-img {
            max-width: 80%; /* Sedikit lebih kecil dari kertas */
            max-height: 80%;
        }
        .item-name {
            margin-top: 15px;
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="sticker-container">
        <img src="<?= $item->wr_matrequest_item_qrcode_image ?>" alt="QR Code" class="qr-code-img">
    </div>
</body>
</html>
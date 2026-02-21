# API Guide - Sinyal Saham Indo + ALIMA Gateway

Base URL contoh:

`https://saham.pondokalima.com/api`

Semua endpoint admin pakai Bearer token Sanctum:

`Authorization: Bearer <TOKEN_ADMIN>`

## 1) Login
- Method: `POST`
- URL: `/auth/login`
- Body JSON:

```json
{
  "email": "admin@sinyalsahamindo.local",
  "password": "password123"
}
```

- Response:

```json
{
  "token": "1|xxxxxxxx",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "role": "admin"
  }
}
```

## 2) Upload Template Bergambar (Admin)
- Method: `POST`
- URL: `/admin/message-templates`
- Content-Type: `multipart/form-data`
- Form fields:
  - `name` (required)
  - `event_type` (required: `birthday|holiday|general`)
  - `religion` (optional, wajib jika `event_type=holiday`)
  - `content` (required)
  - `is_active` (optional: `1/0`)
  - `image_url` (optional URL)
  - `image_file` (optional file image; jika diisi akan override `image_url`)

- Response sukses `201`:

```json
{
  "message": "Template berhasil dibuat.",
  "template": {
    "id": 10,
    "name": "Template Promo",
    "image_url": "https://domain.com/storage/wa-template-images/xxx.jpg"
  }
}
```

## 3) Update Template Bergambar (Admin)
- Method: `POST` + `_method=PUT` (atau native PUT di client yang support multipart PUT)
- URL: `/admin/message-templates/{id}`
- Content-Type: `multipart/form-data`
- Fields sama seperti create.

## 4) Manual Send WA (Admin) - Text / URL / Upload Gambar
- Method: `POST`
- URL: `/admin/wa-blast/manual-send`
- Content-Type: `multipart/form-data`
- Form fields:
  - `whatsapp_number` (required, contoh `628995235298`)
  - `message` (optional)
  - `image_url` (optional)
  - `image_file` (optional)

Catatan: minimal `message` atau gambar (`image_url` / `image_file`) harus ada.

- Response sukses:

```json
{
  "message": "Manual send berhasil.",
  "result": {
    "ok": true
  },
  "image_url": "https://domain.com/storage/wa-manual-images/xxx.jpg"
}
```

## 5) Preview WA Blast dari Template (Admin)
- Method: `POST`
- URL: `/admin/wa-blast/preview`
- Body JSON:

```json
{
  "message_template_id": 10,
  "tier_id": null,
  "religion": null,
  "date": "2026-02-21"
}
```

## 6) Checklist Wajib agar kirim WA sukses
- `.env` aplikasi sinyal:
  - `ALIMA_GATEWAY_BASE_URL`
  - `ALIMA_GATEWAY_APP_API_KEY`
  - `ALIMA_GATEWAY_SESSION_ID`
- Device di ALIMA Gateway harus status `connected`.
- Jalankan:
  - `php artisan storage:link`
  - `php artisan optimize`


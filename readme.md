apa yang telah kita lakukan hari ini adalah memproses users, dan usercontroller.

kita juga tidak lupa menambahkan routes ke usercontroller karena itu penting untuk pengecekan.

contoh di sini saya pakai 

```curl

curl -i -X POST "http://localhost/smkti/gallery-app/backend/api/auth/login" -H "Content-Type: application/json" -d '{"email":"burhan@mail.com","password":"secret"}'

```

## delete
```curl 
curl -i -X DELETE http://localhost/smkti/gallery-app/backend/api/users/1
```

## update user
```curl
curl -i -X PUT http://localhost/smkti/gallery-app/backend/api/users/1 -H "Content-Type: application/json" -d '{"name":"Budi B","password":"newpass"}'
```

## buat user
```curl
curl -i -X POST http://localhost/smkti/gallery-app/backend/api/users -H "Content-Type: application/json" -d '{"name":"Budi","email":"budi@example.com","password":"secret"}'
```

## cek list users
```curl
curl -i http://localhost/smkti/gallery-app/backend/api/users
```

## ambil user by id
```curl
curl -i http://localhost/smkti/gallery-app/backend/api/users/1
```

## Galeri - cek list
```curl
curl -i http://localhost/smkti/gallery-app/backend/api/galleries
```

## Galeri - ambil by id
```curl
curl -i http://localhost/smkti/gallery-app/backend/api/galleries/1
```

## Galeri - buat
```curl
curl -i -X POST http://localhost/smkti/gallery-app/backend/api/galleries -H "Content-Type: application/json" -d '{"title":"Pemandangan","description":"Gunung indah","image_path":"uploads/photo.jpg"}'
```

### Contoh menggunakan link Pinterest
Jika Anda ingin menggunakan link Pinterest (mis. menampilkan gambar via URL Pinterest), kirim field `file` sebagai URL Pinterest:
```curl
curl -i -X POST http://localhost/smkti/gallery-app/backend/api/galleries -H "Content-Type: application/json" -d '{"title":"Pin Cantik","deskripsi":"Dari Pinterest","file":"https://www.pinterest.com/pin/123456789012345678/","author_id":3}'
```

## Galeri - update
```curl
curl -i -X PUT http://localhost/smkti/gallery-app/backend/api/galleries/1 -H "Content-Type: application/json" -d '{"title":"Nama baru","description":"desc baru"}'
```

## Galeri - hapus
```curl
curl -i -X DELETE http://localhost/smkti/gallery-app/backend/api/galleries/1
```

```sql
ALTER TABLE gallery
ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL AFTER `created_at`;
```
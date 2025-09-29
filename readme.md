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
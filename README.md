# README #

### Hot to build and run? ###
When you build the containers and you want to have the app running, you need to execute the command `make build-app`.
This command will build containers, install the Symfony CLI and run the Symfony web-server.


### Hot to run the server? ###
Run the command `make up` to run necessary containers
After that to make the server running, execute the command `make web-server-run` to run the Symfony server.

### How to run code quality check? ###
Execute the command `make check-code-quality`


### How to run tests? ###
Execute the command `make run-tests`

### Examples of the requests to the app ###

#### Create an item in the cart ####
```aiignore
curl --location 'http://localhost:8087/cartItem' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer serviceName' \
--data '{
	"itemId": {itemId},
    "count": {count},
    "userId": {userId}
}'
```

#### Change item from the cart ####
```aiignore
curl --location --request PUT 'http://127.0.0.1:8087/cartItem/{id}' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer serviceName' \
--header 'Cookie: PHPSESSID=d95457268e2807f60e454c1eca5ea9eb; main_auth_profile_token=416d9a' \
--data '{
    "count": {count}
}'
```

#### Delete an item from the cart ####
```aiignore
curl --location --request DELETE 'http://127.0.0.1:8087/cartItem/{id}' \
--header 'Authorization: Bearer serviceName' \
--header 'Cookie: PHPSESSID=d95457268e2807f60e454c1eca5ea9eb; main_auth_profile_token=416d9a' \
--data ''
```

#### Show all items in the cart ####
```aiignore
curl --location 'http://127.0.0.1:8087/cartItems/2' \
--header 'Authorization: Bearer serviceName' \
--header 'Cookie: PHPSESSID=d95457268e2807f60e454c1eca5ea9eb; main_auth_profile_token=416d9a'
```

where `itemId`, `count`, `userId` - int numbers

Here you use a hard-coded token `serviceName` and it will be for test purposes the same.
If you would like to implement the real logic to check the authorisation token, you can make it here `App\Security\AccessTokenHandler`
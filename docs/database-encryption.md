Database Encryption
===================

There are many reasons to encrypt columns in the database, what is beneficial to encrypt will depend on what your database is storing. One thing that should always be protected is credentials, and this document will assume a Hawk key _hawk_key_ and basic authentication password _basic_key_.

_**Always store passwords as salted hash.** Use `password_​hash()` to create hashes and `password_​verify()` to verify an authenticating users password input._

As _basic_key_ is a regular password, it's being stored as a hash and is unreadable. Should the database be hacked then the hash would have to be cracked in order to get the actual password.
Encrypting your hashes adds another layer of defense, increasing the complexity and time required for an adversary to get the password.

The Hawk key cannot be stored as a hash as it is required by both client and server to authenticate and sign messages. For this reason it should be a large randomized key and never picked by a user.
Here encryption becomes very important: it prevents database administrators and other database users (including hackers) from simpy reading and copying the key.

The [defuse/php-encryption](https://packagist.org/packages/defuse/php-encryption) library provides a simple and secure way to use encryption, make sure you read the documentation.

All you need to add for your application to handle encrypted Hawk key and password hash is:

```php
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key as CryptoKey;

class User extends Model
{
    private function loadCryptoKey()
    {
        // Load your crypto key here
    }

    public function beforeSave()
    {
        $keyAscii = $this->loadCryptoKey();
        if ($keyAscii) {
            $encryptionKey = CryptoKey::loadFromAsciiSafeString($keyAscii);
            if ($this->basic_key) {
                $this->basic_key = Crypto::encrypt($this->basic_key, $encryptionKey);
            }
            if ($this->hawk_key) {
                $this->hawk_key = Crypto::encrypt($this->hawk_key, $encryptionKey);
            }
        }
    }

    public function afterFetch()
    {
        $keyAscii = $this->loadCryptoKey();
        if ($keyAscii) {
            $encryptionKey = CryptoKey::loadFromAsciiSafeString($keyAscii);
            if ($this->basic_key) {
                $this->basic_key = Crypto::decrypt($this->basic_key, $encryptionKey);
            }
            if ($this->hawk_key) {
                $this->hawk_key = Crypto::decrypt($this->hawk_key, $encryptionKey);
            }
        }
    }

    public function afterSave()
    {
        $keyAscii = $this->loadCryptoKey();
        if ($keyAscii) {
            $encryptionKey = CryptoKey::loadFromAsciiSafeString($keyAscii);
            if ($this->basic_key) {
                $this->basic_key = Crypto::decrypt($this->basic_key, $encryptionKey);
            }
            if ($this->hawk_key) {
                $this->hawk_key = Crypto::decrypt($this->hawk_key, $encryptionKey);
            }
        }
    }
}
```

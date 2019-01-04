Caching Models
==============

Lookups from a relational database can be slow, in a relative way, especially
as the load increases. Caching is often implemented to avoid unnecessary
repeat lookups and reduce database load.

Freischutz includes the class Freischutz\Utility\CacheableModel for caching
models with minimal work. The model which will be cached must extend the
CacheableModel class:

```php
<?php
namespace Reference\Models;

use Freischutz\Utility\CacheableModel;

class CacheExample extends CacheableModel
{
}
```

To cache the results from find() or findFirst(), the $parameter array must
include the key 'cache' with an array value. The cache array has the following
key-value pairs (as well as any other pairs Phalcon implements):

* key: unique key to store the results under.
* lifetime: expiry time for the key in seconds, default 300.
* service: the DI caching service, default 'cache'.

The key must be unique for the query within the model, e.g. model 'a' should
only have one query that caches with the key '1', but model 'b' may also use
the key '1'. For a findFirst() call that queries by primary key it would make
sense to use the primary key as the cache key.

Caching presents the problem of outdated information: after retrieving and
caching a model, any changes to that model will not be seen until either the
cache item expires or is removed. If all modifications of the data go through
our application then it becomes easy to avoid this problem. By knowing the
cache key we can remove the cached model after a modification using the
uncache() method.

The following code for a controller shows an example of how to cache a
findFirst() call that uses primary key and find() that retrieves all the models
records. It also shows how to use uncache() to remove the outdated cached items
on a successful deletion of a model.

```php
<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Reference\Models\CacheExample;
use Phalcon\Mvc\Controller;

class CacheExampleController extends Controller
{
    public function getAction()
    {
        $id = $this->dispatcher->getParam('id');

        $response = new Response();

        if (!$id) {
            $models = CacheExample::find(array(
                'cache' => array('key' => 'all')
            ));
            $data = $models->toArray();
            $response->ok($data);
        } else {
            if ($model = CacheExample::findFirst(array(
                'id=:id:',
                'bind' => array('id' => $id),
                'cache' => array('key' => $id)
            ))) {
                $response->ok($model);
            } else {
                $response->notFound("Model '$id' not found.");
            }
        }

        return $response;
    }

    public function deleteAction()
    {
        $id = $this->dispatcher->getParam('id');

        $response = new Response();

        if (!$model = CacheExample::findFirst($id)) {
            $response->notFound("Model '$id' not found.");
        } elseif ($model->delete()) {
            $response->ok("Model '$id' deleted.");
            $model->uncache->delete($id);
            $model->uncache->delete('all');
        } else {
            // Do something sane
        }

        return $response;
    }
}
```

Using Database for ACL
======================

* Roles model requires the attribute 'name', the attribute 'description' may also be used.
* Inherits model requires the attribute 'role_name' and 'inherit'.
* Resources model requires the attribute 'controller' and 'action', the attribute 'description' may also be used.
* Rules model requires the attribute 'role_name', 'resource_controller', resource_action', and 'policy'.

Any additional attributes will not impact the ACL system.

The database tables' columns may differ from the model attributes, the Phalcon\Mvc\Model class supports overriding both the model source and column mappings:

```php
use Phalcon\Mvc\Model;

class AclRule extends Model
{
    /**
     * Use a 'acl' as table name instead of 'acl_rule'.
     */
    public function getSource()
    {
        return 'acl';
    }

    /**
     * Remap column names.
     */
    public function columnMap()
    {
        // Keys are table column names and values are model attributes
        return array(
            'security_group_name' => 'role_name',
            'resource_controller' => 'resource_controller',
            'resource_action'     => 'resource_action',
            'policy'              => 'policy',
        );
    }
}
```

This way the models may be grouped in the application, e.g.:

* AclInherit
* AclResource
* AclRole
* AclRule

while leaving you free to name your tables (and columns) in a manner that suits your schema.

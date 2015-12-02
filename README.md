# restful.class.php

Use example
```
<?php
$rest=new RESTful('promocode', 'id,code,order', array(
    // Sanitize options
    'id'    => FILTER_SANITIZE_NUMBER_INT,
    'code'  => '/[^0-9a-zA-Z]/',
    'order' =>  function($val=null){
        if(!empty($val)) return $val;
        return null;
    }
));
print_r($rest->data);
print $rest('code');
```

Separates GET/POST data into two arrays:

**1st optional parameter** defines this request name. May be helpful for permissions definition

`$rest->data` // Request data payload. List of it defines the **2th optional construct() parameter**

**3rd optional parameter** defines method of sanitization. Default is FILTER_SANITIZE_STRING for the filter_var()

`$rest->scope` // authorization parameters. List of it defines the **4th optional construct() parameter**

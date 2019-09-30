# CSRF Protection
Cross-Site Request Forgery (CSRF) is an attack that forces an end user to execute unwanted actions on a web application 
in which they're currently authenticated. [More](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_\(CSRF\))

## AJAX request CSRF Protection
To protect controllers against CSRF AJAX `@CsrfProtection` annotation should be used. This annotation may be used for whole 
controller of for individual actions.
[Double Submit Cookie](https://www.owasp.org/index.php/Cross-Site_Request_Forgery_\(CSRF\)_Prevention_Cheat_Sheet#Double_Submit_Cookie) technique used for AJAX request protection,
each AJAX request must have `X-CSRF-Header` header with valid token value, this header is added by default for all AJAX requests.
Current token value is stored in the cookie `_csrf` for HTTP connections and `https-_csrf` for HTTPS.

Controller level protection
```php
// ...

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @CsrfProtection
 */
class AjaxController extends Controller
{
    /**
     * @Route("/ajax/change-stus/{statusName}", name="acme_ajax_change_status", methods={"POST"})
     */
    public function performAction($statusName)
    {
        // ...
    }
}
```

Action level protection
```php
// ...

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AjaxController extends Controller
{
    /**
     * @Route("/ajax/change-stus/{statusName}", name="acme_ajax_change_status", methods={"POST"})
     * @CsrfProtection
     */
    public function performAction($statusName)
    {
        // ...
    }
}
```

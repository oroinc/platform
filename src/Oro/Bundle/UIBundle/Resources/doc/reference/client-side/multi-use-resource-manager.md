<a name="module_MultiUseResourceManager"></a>
## MultiUseResourceManager ⇐ <code>[BaseClass](./base-class.md)</code>
Allows to create/remove resource that could be used by multiple holders.

Use case:
```javascript
var backdropManager = new MultiUseResourceManager({
    listen: {
        'construct': function() {
            $(document.body).addClass('backdrop');
        },
        'dispose': function() {
            $(document.body).removeClass('backdrop');
        }
    }
});

// 1. case with Ids
var holderId = backdropManager.hold();
// then somewhere
backdropManager.release(holderId);

// 2. case with holder object
backdropManager.hold(this);
// then somewhere, please note that link to the same object should be provided
backdropManager.release(this);

// 2. case with holder identifier
backdropManager.hold(this.cid);
// then somewhere, please note that link to the same object should be provided
backdropManager.release(this.cid);
```

**Extends:** <code>[BaseClass](./base-class.md)</code>  

* [MultiUseResourceManager](#module_MultiUseResourceManager) ⇐ <code>[BaseClass](./base-class.md)</code>
  * [.counter](#module_MultiUseResourceManager#counter) : <code>number</code>
  * [.isCreated](#module_MultiUseResourceManager#isCreated) : <code>boolean</code>
  * [.holders](#module_MultiUseResourceManager#holders) : <code>Array</code>
  * [.constructor()](#module_MultiUseResourceManager#constructor)
  * [.hold(holder)](#module_MultiUseResourceManager#hold) ⇒ <code>\*</code>
  * [.release(id)](#module_MultiUseResourceManager#release)
  * [.isReleased(id)](#module_MultiUseResourceManager#isReleased) ⇒ <code>boolean</code>
  * [.checkState()](#module_MultiUseResourceManager#checkState)
  * [.dispose()](#module_MultiUseResourceManager#dispose)

<a name="module_MultiUseResourceManager#counter"></a>
### multiUseResourceManager.counter : <code>number</code>
Holders counter

**Kind**: instance property of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
**Access:** protected  
<a name="module_MultiUseResourceManager#isCreated"></a>
### multiUseResourceManager.isCreated : <code>boolean</code>
True if resource is created

**Kind**: instance property of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
<a name="module_MultiUseResourceManager#holders"></a>
### multiUseResourceManager.holders : <code>Array</code>
Array of ids of current resource holders

**Kind**: instance property of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
<a name="module_MultiUseResourceManager#constructor"></a>
### multiUseResourceManager.constructor()
**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
<a name="module_MultiUseResourceManager#hold"></a>
### multiUseResourceManager.hold(holder) ⇒ <code>\*</code>
Holds resource

**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
**Returns**: <code>\*</code> - holder identifier  

| Param | Type | Description |
| --- | --- | --- |
| holder | <code>\*</code> | holder identifier |

<a name="module_MultiUseResourceManager#release"></a>
### multiUseResourceManager.release(id)
Releases resource

**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  

| Param | Type | Description |
| --- | --- | --- |
| id | <code>\*</code> | holder identifier |

<a name="module_MultiUseResourceManager#isReleased"></a>
### multiUseResourceManager.isReleased(id) ⇒ <code>boolean</code>
Returns true if resource holder has been already released

**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  

| Param | Type | Description |
| --- | --- | --- |
| id | <code>\*</code> | holder identifier |

<a name="module_MultiUseResourceManager#checkState"></a>
### multiUseResourceManager.checkState()
Check state, creates or disposes resource if required

**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  
**Access:** protected  
<a name="module_MultiUseResourceManager#dispose"></a>
### multiUseResourceManager.dispose()
**Kind**: instance method of <code>[MultiUseResourceManager](#module_MultiUseResourceManager)</code>  

## MultiUseResourceManager ⇐ [BaseClass](./base-class.md)

<a name="module_MultiUseResourceManager"></a>

Allows to create/remove resource that could be used by multiple holders.

Use case:
```javascript
var backdropManager = new MultiUseResourceManager({
    listen: {
        'constructResource': function() {
            $(document.body).addClass('backdrop');
        },
        'disposeResource': function() {
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

**Extends:** [BaseClass](./base-class.md)  

* [MultiUseResourceManager](#module_MultiUseResourceManager) ⇐ [BaseClass](./base-class.md)
  * [.counter](#module_MultiUseResourceManager#counter) : `number`
  * [.isCreated](#module_MultiUseResourceManager#isCreated) : `boolean`
  * [.holders](#module_MultiUseResourceManager#holders) : `Array`
  * [.constructor()](#module_MultiUseResourceManager#constructor)
  * [.hold(holder)](#module_MultiUseResourceManager#hold) ⇒ `*`
  * [.release(id)](#module_MultiUseResourceManager#release)
  * [.isReleased(id)](#module_MultiUseResourceManager#isReleased) ⇒ `boolean`
  * [.checkState()](#module_MultiUseResourceManager#checkState)
  * [.dispose()](#module_MultiUseResourceManager#dispose)

<a name="module_MultiUseResourceManager#counter"></a>
### multiUseResourceManager.counter : `number`
Holders counter

**Kind**: instance property of [MultiUseResourceManager](#module_MultiUseResourceManager)  
**Access:** protected  
<a name="module_MultiUseResourceManager#isCreated"></a>
### multiUseResourceManager.isCreated : `boolean`
True if resource is created

**Kind**: instance property of [MultiUseResourceManager](#module_MultiUseResourceManager)  
<a name="module_MultiUseResourceManager#holders"></a>
### multiUseResourceManager.holders : `Array`
Array of ids of current resource holders

**Kind**: instance property of [MultiUseResourceManager](#module_MultiUseResourceManager)  
<a name="module_MultiUseResourceManager#constructor"></a>
### multiUseResourceManager.constructor()
**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  
<a name="module_MultiUseResourceManager#hold"></a>
### multiUseResourceManager.hold(holder) ⇒ `*`
Holds resource

**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  
**Returns**: `*` - holder identifier  

| Param | Type | Description |
| --- | --- | --- |
| holder | `*` | holder identifier |

<a name="module_MultiUseResourceManager#release"></a>
### multiUseResourceManager.release(id)
Releases resource

**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  

| Param | Type | Description |
| --- | --- | --- |
| id | `*` | holder identifier |

<a name="module_MultiUseResourceManager#isReleased"></a>
### multiUseResourceManager.isReleased(id) ⇒ `boolean`
Returns true if resource holder has been already released

**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  

| Param | Type | Description |
| --- | --- | --- |
| id | `*` | holder identifier |

<a name="module_MultiUseResourceManager#checkState"></a>
### multiUseResourceManager.checkState()
Check state, creates or disposes resource if required

**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  
**Access:** protected  
<a name="module_MultiUseResourceManager#dispose"></a>
### multiUseResourceManager.dispose()
**Kind**: instance method of [MultiUseResourceManager](#module_MultiUseResourceManager)  

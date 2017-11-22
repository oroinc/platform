## persistentStorage

<a name="module_persistentStorage"></a>

* [persistentStorage](#module_persistentStorage)
  * [persistentStorage](#exp_module_persistentStorage--persistentStorage) ⏏
    * [.length](#module_persistentStorage--persistentStorage.length) : `number`
    * [.getItem(sKey)](#module_persistentStorage--persistentStorage.getItem) ⇒ `string`
    * [.key(nKeyId)](#module_persistentStorage--persistentStorage.key)
    * [.setItem(sKey, sValue)](#module_persistentStorage--persistentStorage.setItem)
    * [.removeItem(sKey)](#module_persistentStorage--persistentStorage.removeItem)
    * [.hasOwnProperty()](#module_persistentStorage--persistentStorage.hasOwnProperty)
    * [.clear()](#module_persistentStorage--persistentStorage.clear)

<a name="exp_module_persistentStorage--persistentStorage"></a>
### persistentStorage ⏏
Provides clint-side storage
Uses localStorage if supported, otherwise cookies
Realizes Storage Interface https://developer.mozilla.org/en-US/docs/Web/API/Storage

**Kind**: Exported member  
<a name="module_persistentStorage--persistentStorage.length"></a>
#### persistentStorage.length : `number`
Returns an integer representing the number of data items stored in the Storage object.

**Kind**: static property of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  
**Read only**: true  
<a name="module_persistentStorage--persistentStorage.getItem"></a>
#### persistentStorage.getItem(sKey) ⇒ `string`
When passed a key name, will return that key's value.

**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  

| Param | Type |
| --- | --- |
| sKey | `string` | 

<a name="module_persistentStorage--persistentStorage.key"></a>
#### persistentStorage.key(nKeyId)
When passed a number n, this method will return the name of the nth key in the storage.

**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  

| Param | Type |
| --- | --- |
| nKeyId | `number` | 

<a name="module_persistentStorage--persistentStorage.setItem"></a>
#### persistentStorage.setItem(sKey, sValue)
When passed a key name and value, will add that key to the storage, or update that key's value if it
already exists.

**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  

| Param | Type |
| --- | --- |
| sKey | `string` | 
| sValue | `string` | 

<a name="module_persistentStorage--persistentStorage.removeItem"></a>
#### persistentStorage.removeItem(sKey)
When passed a key name, will remove that key from the storage.

**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  

| Param | Type |
| --- | --- |
| sKey | `string` | 

<a name="module_persistentStorage--persistentStorage.hasOwnProperty"></a>
#### persistentStorage.hasOwnProperty()
**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  
<a name="module_persistentStorage--persistentStorage.clear"></a>
#### persistentStorage.clear()
When invoked, will empty all keys out of the storage.

**Kind**: static method of [persistentStorage](#exp_module_persistentStorage--persistentStorage)  

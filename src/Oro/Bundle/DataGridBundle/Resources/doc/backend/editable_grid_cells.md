Editable data grid cells
========================

## Overview

Editable data grid cells is used to change data directly in grid. Currently supported select and radio buttons editable cells.

## Example of Use

Demonstrative example is ``customer-product-visibility-grid``. On product edit adding customers grid with ``visibilityForCustomer`` column.
User can change visibility this product for customer. For example, imagine we already have entity  ``CustomerProductVisibility`` with relations between
``customer``, ``product`` and enum ``visibility``.

For implementation this developer should perform three simple steps.

Step 1. Mark editable some fields in datagrid config and add cellSelection
--------------------------------------------------------------------------

Example of grid configuration:
``` yml
    customer-product-visibility-grid:
        source:
            acl_resource:      acme_product_view
            type:              orm
            query:
                select:
                    - customer.id
                    - customer.name
                    - IDENTITY(customerProductVisibility.visibility) as visibility
                from:
                    - { table: %acme_customer.entity.customer.class%, alias: customer }
                join:
                    left:
                        - { join: %acme_customer.entity.customer_product_visibility.class%, alias: customerProductVisibility, conditionType: WITH, condition: 'customerProductVisibility.customer = customer' }
                where:
                    and:
                        - IDENTITY(customerProductVisibility.product) = :product_id
            bind_parameters:
                - product_id
        columns:
            name:
                label: acme.customer.name.label
            visibility:
                label: acme.customer.product_visibility.label
                frontend_type: select
                editable: true # this cell will be editable
                expanded: true # this cell will be rendered as radio buttons
                choices: @oro_entity_extend.enum_value_provider->getEnumChoicesByCode('cust_prod_visibility')
        options:
            cellSelection:
                dataField: id
                columnName:
                    - visibility
                selector: '#customer-product-visibility-changeset'
        properties:
            id: ~
```
Common options:

``editable`` option - mark cell as editable
``cellSelection`` option - add behavior of selecting rows on frontend:

Event listener ``\Oro\Bundle\DataGridBundle\EventListener\CellSelectionListener`` applied to all grids with "cellSelection" option.
If this option is specified this listener will add js module ``orodatagrid/js/datagrid/listener/change-editable-cell-listener`` to handle changes behavior on frontend.

To receive select options or radio buttons values using ``oro_entity_extend.enum_value_provider`` service which provide ability to get enum values by enum code.

Step 2. Add ``oro_entity_changeset`` to form type
-------------------------------------------------

```php

    $builder
        ... 
        ->add(
            'visibilityForCustomer',
            EntityChangesetType::NAME,
            [
                'class' => 'OroB2B\Bundle\CustomerBundle\Entity\Customer'
            ]
        )
        ...
```

Option ``class`` in ``\Oro\Bundle\FormBundle\Form\Type\EntityChangesetType`` is required. It is class name of grid item.

Then displayed this field in template, class name is selector specified in the ``datagrid.yml`` config ``changeset: '.customer-product-visibility-changeset'``:
``` twig

    ...
    form_row(form.visibilityForCustomer, {'attr': {'id': 'customer-product-visibility-changeset'}})
    ...
```

As a result we will have hidden field ``visibilityForCustomer`` which contains  data in current format
	
```json

    {'<customerId>' : {'<visibility>' : '<value>', ...}, ... }
```

Step 3. Create custom form handler with processing editable grid cells
----------------------------------------------------------------------
For convert enum value in handler use method ``getEnumValueByCode`` ``oro_entity_extend.enum_value_provider`` service

```php

        /**
         * Process form
         *
         * @param Product $product
         * @return bool True on successful processing, false otherwise
         */
        public function process(Product $product)
        {
            $this->form->setData($product);
            if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
                $this->form->submit($this->request);
                
                if ($this->form->isValid()) {
                    $this->onSuccess($product);
                    
                    return true;
                }
            }
            
            return false;
        }
        
        /**
         * "Success" form handler
         *
         * @param Product $product
         */
        protected function onSuccess(Product $product)
        {
            $changeSet = $this->form->get('visibilityForCustomer')->getData();
            
            foreach ($changeSet as $item) {
                /** @var Customer $customer */
                $customer = $item['entity'];
                $productVisibility = $this->manager->getRepository('OroB2BCustomerBundle:CustomerProductVisibility')
                    ->findOneBy(['product' => $product, 'customer' => $customer]);
                    
                if (!$productVisibility) {
                    $productVisibility = new CustomerProductVisibility();
                    $productVisibility->setProduct($product);
                    $productVisibility->setCustomer($customer);
                }
                
                $visibility = $this->enumValueProvider->getEnumValueByCode(
                    'cust_prod_visibility',
                    $item['data']['visibility']
                );
                
                $productVisibility->setVisibility($visibility);
                $this->manager->persist($productVisibility);
            }
            
            $this->manager->persist($product);
            $this->manager->flush();
        }
```
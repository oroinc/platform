Documentation resource format
=============================

The documentation resource file is a regular .md document that contains description about one or multiple resources.
The only requirement for such document is it should be written in particular format:

Each resource documentation should starts from '#' (h1) header that contains Fully-Qualified Class Name (FQCN) of the resource. For instance: 

    # Acme\Bundle\AcmeBundle\Entity\AcmeEntity

As already mentioned above, a single documentation file may contain multiple resource documentations, e.g.:

    # Acme\Bundle\AcmeBundle\Entity\AcmeEntity1
    ...
    # Acme\Bundle\AcmeBundle\Entity\AcmeEntity2

At the next level `##` (h2) one of the documentation sections should be specified, e.g.:

    # Acme\Bundle\AcmeBundle\Entity\AcmeEntity

    ## Actions
    ...
    ## FIELDS
    ...
    ## filters

The letters case of the section name is no matter. They are used only by documentation parser to identify the documentation part.  
The following table describes the purposes of each documentation section:

| Section name | Description | Example |
| --- | --- | --- |
| Actions | Followed by action name and contains a description for particular action. | GET, GET_LIST, CREATE, UPDATE, etc. |
| Fields | Followed by field name and contains a description for particular field. At the same time it is possible to specify the description for field and for particular action as well. | id, name, createdAt, updatedAt, etc. |
| Filters | Followed by field name and contains a description for particular field, but in case it will be used for filtering purposes. | id, name, createdAt, updatedAt, etc. |

The third level `###` (h3) is field name or filter field name:

	# Acme\Bundle\AcmeBundle\Entity\AcmeEntity
	
	## Actions
	
	### GET
	
	Description for GET action. May contain any formatting e.g.: ordered or unordered lists, request or response examples, links, text in bold or italic, etc.
	
	### CREATE
	
	Description for CREATE action
	
	## Fields
	
	### id
	
	Description for ID field
	
	### name
	
	Description for NAME field
	
	## Filters
	
	### id
	
	Description for ID filter
	
	### name
	
	Description for NAME filter
	
	### createdAt
	
	Description for CREATED AT filter

And for the case then it is needed to specify the description for field and for particular action, the fourth level `####` (h4) header should be added, so the formatting will looks like this:

	# Acme\Bundle\AcmeBundle\Entity\AcmeEntity
	
	## FIELDS
	
	### name
	
	Regular description for NAME field
	
	#### GET
	
	Description for NAME field and for GET action


Please note, the `Filters` section do not supports the 4th level (per action type) of description.

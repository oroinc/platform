Email Ownership
=======================

Email ownership differs from standard model slightly. To determine permissions for email object emailUser relation is used.
In such way several users can be owners of one email object. For example, when two users synchronize
email conversation at the same time via IMAP, one email object is created and two relations with appropriate users and
for each user appropriate access permission will be applied to the same email via userEmail relation. 

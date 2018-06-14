Authentication
==============

All connections to socket server are protected with Sync authentication tickets.

Before connect, the client should receive the connection ticket and pass it as a part of connection URL as the `ticket` query parameter.

For the frontend clients, the authentication ticket can be received by calling the POST request to `oro_sync_ticket` route. As response 
of this request will be JSON object with `ticket` field that will contain one-time authentication ticket.

In case if client is backend client, the authentication ticket can be received by calling `generateTicket` method of
[oro_sync.authentication.ticket_provider](../../Authentication/Ticket/TicketProvider.php) service.

All tickets have limited lifetime - 300 seconds by default.

In case if authentication was success, the client would be able to subscribe and send new messages to topics.

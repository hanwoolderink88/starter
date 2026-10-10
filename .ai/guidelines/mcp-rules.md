# MCP Rules

## Core Principle

Claude works with the application through its MCP server as a full team member: what a user can do in the interface, they can do through a tool, with the same policies, the same validation and the same Actions. A tool is just another thin surface, like a controller.

## Structure

- One endpoint: `routes/ai.php` registers `Mcp::web('/mcp', AppServer::class)` behind `auth:api` (OAuth via Passport) and `throttle:mcp` (`services.mcp.per_minute` calls per account per minute).
- `app/Mcp/Servers/AppServer.php` is only a composition root that spreads each feature's `{Feature}Server::TOOLS`. Never put feature logic in `app/Mcp/`.
- Tools live in their feature: `app/Features/{Feature}/Mcp/Tools/{Verb}{Model}Tool.php`, named `{model}_{verb}` (`user_update`) or `search_{model}` / `list_{models}`.

## Parity

- Every user-facing function of a feature has a tool, or there is a reason why not. Impersonation has none (it is a browser session); managing roles has none on purpose (sensitive configuration where an agent's mistake changes everyone's rights).
- `shouldRegister()` hides a tool without the permission, and `handle()` still authorizes with `Gate::authorize(...)` against the **same policy** as the controller: hiding is not authorization.
- Validate with the same rules as the FormRequest (including `Rule::unique(...)->withoutTrashed()` and the required `version` for updates) and call the **same Action**. No business logic in the tool.
- An update tool requires `version`, and show and search tools return it. A `StaleVersionException` reaches the agent as a tool error naming the current version, so it can fetch and retry.
- Domain invariants are typed exceptions from the Action (`LastAdministratorException`), turned into `Response::error(...)` by the tool. An untyped exception reaches the agent as a generic error.
- An agent names roles, the forms send ids: use `Mcp/Concerns/ResolvesRoleNames`.

## Traceable and Live

Build the context with `ChangeContext::fromMcpRequest($request)`: the token's user is the actor and the source is `mcp`, so the change is logged as such and every open browser toasts "Jane via Claude".

## Annotations

Mark a tool that changes nothing `#[IsReadOnly]`, and one that deletes or irreversibly changes data `#[IsDestructive]` (from `Laravel\Mcp\Server\Tools\Annotations`). A test checks the list.

## Personal Data

What a tool returns leaves the application and reaches the AI provider. Return only what the agent needs to do its work: `search_activity` leaves out IP addresses and user agents, `list_roles` shows permissions only to who may manage roles.

## Testing

Test each tool through `AppServer::actingAs($user)->tool(...)`: that it is hidden without the permission, that it changes the same data as the web with `source` = `mcp`, and that refused changes come back as tool errors.

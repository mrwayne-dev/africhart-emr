# Wayne CLI - Reference Guide

Local Apache development tool for managing `.test` sites with HTTP, HTTPS, and ngrok sharing.

---

## Commands

### `wayne setup <project>`
Creates an **HTTP-only** virtual host for a project.

- Writes an Apache config to `/etc/apache2/sites-available/<project>.test.conf`
- Adds `127.0.0.1 <project>.test` to `/etc/hosts`
- Enables the site and reloads Apache

```bash
wayne setup lymora
# Visit: http://lymora.test
```

---

### `wayne serve <project>`
Creates an **HTTPS** virtual host using a locally-trusted certificate via `mkcert`.

- Generates SSL certs stored in `/etc/ssl/wayne/` (not in the project folder)
- Enables `mod_ssl` and `mod_rewrite`
- HTTP redirects permanently to HTTPS

```bash
wayne serve lymora
# Visit: https://lymora.test
```

> Requires `mkcert` to be installed: `sudo apt install mkcert`

---

### `wayne share <project> [--webhook]`
Exposes the local site publicly via **ngrok**.

- Detects HTTP vs HTTPS automatically from the site config
- `--webhook` flag enables stdout logging (useful for webhook debugging)

```bash
wayne share lymora
wayne share lymora --webhook
```

> Requires an active site config. Run `setup` or `serve` first.
> Requires `ngrok` installed and authenticated.

---

### `wayne open <project>`
Opens the project in your default browser.

- Detects HTTP vs HTTPS automatically
- Uses `xdg-open` on Linux

```bash
wayne open lymora
```

---

### `wayne list`
Lists all configured `.test` sites and their scheme.

```bash
wayne list
# Configured sites:
#   lymora   ->  https://lymora.test
#   mgbah    ->  http://mgbah.test
```

---

### `wayne remove <project>`
Fully removes a site configuration.

- Disables and deletes the Apache config
- Removes the entry from `/etc/hosts`
- Deletes SSL certificates from `/etc/ssl/wayne/`
- Reloads Apache

```bash
wayne remove lymora
```

---

### `wayne logs <project>`
Tails the Apache error and access logs for a project live.

```bash
wayne logs lymora
# Press Ctrl+C to stop
```

Logs are located at:
- `/var/log/apache2/<project>-error.log`
- `/var/log/apache2/<project>-access.log`

---

### `wayne status`
Shows the current state of Apache and ngrok.

```bash
wayne status
# Apache2:
#   Status : running
#   Sites  : 2 .test site(s) enabled
#
# ngrok:
#   Status : not running
```

---

## Project Directory

All projects are expected to exist under:

```
/home/mrwayne/Documents/wayne/web_dev/<project-name>/
```

Project names must only contain letters, numbers, hyphens, and underscores.

---

## Typical Workflows

### New project (HTTP)
```bash
wayne setup myproject
wayne open myproject
```

### New project (HTTPS)
```bash
wayne serve myproject
wayne open myproject
```

### Share with a client or test webhooks
```bash
wayne serve myproject       # if not already set up
wayne share myproject
```

### Debug a site
```bash
wayne logs myproject
wayne status
```

### Tear down a site
```bash
wayne remove myproject
```

---

## SSL Certificate Location

Certs are stored in `/etc/ssl/wayne/`, not inside the project folder, so they are never accidentally served as static files.

| File | Path |
|------|------|
| Certificate | `/etc/ssl/wayne/<project>.test.pem` |
| Private key | `/etc/ssl/wayne/<project>.test-key.pem` |

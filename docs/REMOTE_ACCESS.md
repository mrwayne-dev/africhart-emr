# Remote Server Access & Automation (SSH)

How we connected a **local machine** to the **live cPanel server** so commands can be run on
the server directly from the laptop — including letting an assistant (Claude) drive
deployments "in the background." This document explains *what* the setup is, *why* each piece
exists, *exactly how we built it*, and *how to reproduce it* for another server or another
person.

> **Security note:** this file uses **placeholders** (`<SERVER_IP>`, `<SSH_PORT>`,
> `<CPANEL_USER>`, `<PASSPHRASE>`, …). Never commit your real host, username, key passphrase,
> or any secret into a repository — especially a public one. Keep real values in an
> **uncommitted** local note (e.g. `~/.ssh/africhart-notes.txt`).

---

## Table of contents

1. [The goal & the mental model](#1-the-goal--the-mental-model)
2. [Why SSH-from-local (not VS Code Remote-SSH)](#2-why-ssh-from-local-not-vs-code-remote-ssh)
3. [The two authentication layers](#3-the-two-authentication-layers)
4. [Prerequisites](#4-prerequisites)
5. [Step-by-step setup (what we did)](#5-step-by-step-setup-what-we-did)
6. [The persistent-agent pattern (the key trick)](#6-the-persistent-agent-pattern-the-key-trick)
7. [Using it day to day](#7-using-it-day-to-day)
8. [Setting it up for another server or person](#8-setting-it-up-for-another-server-or-person)
9. [Troubleshooting](#9-troubleshooting)
10. [Security model & hardening](#10-security-model--hardening)
11. [Revoking access (teardown)](#11-revoking-access-teardown)
12. [Quick reference](#12-quick-reference)

---

## 1. The goal & the mental model

We want to run commands on the production server **from the laptop**, non-interactively, so
that scripts (and an assistant) can do things like `git pull`, run database migrations, and
sync files — without someone manually typing into the cPanel web Terminal each time.

The whole thing is just **SSH** (Secure Shell): an encrypted channel from the laptop to the
server. Once a single `ssh <server> '<command>'` works without prompting, *any* tool on the
laptop can use it — including automation.

```
   ┌──────────────┐        SSH (encrypted)         ┌──────────────────────┐
   │   Laptop     │  ───────────────────────────►  │   cPanel server      │
   │ (you/Claude) │   ssh africhart 'git pull'     │  africhart.mgbah.dev │
   └──────────────┘                                └──────────────────────┘
          │                                                   │
          │  forwards the laptop's key on demand              │ uses the forwarded
          └───────────────────────────────────────────────►  key to reach GitHub
                                                              ▼
                                                       ┌────────────┐
                                                       │  GitHub    │
                                                       └────────────┘
```

Two ideas make it work smoothly:
- An **ssh-agent** holds your unlocked key in memory so you only type the passphrase once.
- **Agent forwarding** lets the *server* borrow your laptop's key to authenticate onward to
  GitHub (so `git pull` works on the server without storing a separate key there).

---

## 2. Why SSH-from-local (not VS Code Remote-SSH)

There are two ways to "work on the server from VS Code":

| Approach | What it does | Verdict for shared cPanel |
|---|---|---|
| **VS Code Remote-SSH** | Opens the *server* as your editor workspace; downloads & runs a VS Code "server" process on the host | ❌ Usually **fails** on shared/CloudLinux hosting — the host blocks or kills the helper process (CageFS, resource limits). Also moves your tooling off the local repo. |
| **SSH-from-local (what we use)** | Keep the repo + tools local; run individual commands on the server over SSH | ✅ Works anywhere SSH works (the cPanel **Terminal** already proves SSH is available). Lightweight, scriptable. |

So we keep everything local and reach into the server with `ssh`/`rsync` as needed.

---

## 3. The two authentication layers

These are **independent** and people often confuse them:

1. **Laptop → Server.** Proven by your **laptop's SSH key** being listed in the server's
   `~/.ssh/authorized_keys`. This is what we set up.
2. **Server → GitHub.** When the server runs `git pull`, *it* must authenticate to GitHub.
   The server has its own key, but ours is **passphrase-locked**, so it can't be used
   non-interactively. We solve this with **agent forwarding**: the server borrows the
   laptop's key (which is already authorized on your GitHub account) for the duration of the
   SSH session.

Keep them separate in your head: layer 1 is "can I get in?", layer 2 is "once in, can the
server pull code?".

---

## 4. Prerequisites

On the **laptop**:
- An SSH client (`ssh -V` — preinstalled on macOS/Linux; on Windows use WSL or Git Bash).
- An SSH key pair. Check: `ls ~/.ssh/*.pub`. If none, create one:
  ```bash
  ssh-keygen -t ed25519 -C "you@example.com"
  ```

On the **server** (cPanel):
- SSH access must be enabled. If the cPanel **Terminal** opens, you have shell access. Some
  hosts require you to request "SSH access" activation from support first.

You also need three connection facts: the server **host/IP**, the SSH **port**, and your
**cPanel username**. Find them under cPanel → **SSH Access**, or your hosting welcome email.

---

## 5. Step-by-step setup (what we did)

### 5.1 Find the SSH port

cPanel hosts often use a **non-standard** SSH port (22 is frequently closed). If you don't
know it, probe the common ones from the laptop:

```bash
for p in 22 21098 2222 7822 18765; do
  timeout 4 bash -c "echo > /dev/tcp/<SERVER_IP>/$p" 2>/dev/null \
    && echo "port $p: OPEN" || echo "port $p: closed"
done
```

In our case the open port was **`<SSH_PORT>`** (a common Namecheap/Spaceship cPanel port).
Note: cPanel's web port (2083) is **not** SSH.

### 5.2 Authorize the laptop's public key on the server

Print the laptop public key:

```bash
cat ~/.ssh/id_ed25519.pub
```

Then add it to the server. Easiest path — paste this into the **cPanel Terminal**:

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo 'ssh-ed25519 AAAA...your-public-key... you@example.com' >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

(GUI alternative: cPanel → **SSH Access → Manage SSH Keys → Import Key**, paste the public
key, then **Manage → Authorize**.)

> Only ever copy the **`.pub`** (public) file. The private key (`id_ed25519`, no extension)
> never leaves the laptop.

### 5.3 Create an SSH config alias

So you can type `ssh africhart` instead of a long command. Edit `~/.ssh/config`:

```sshconfig
Host africhart
    HostName <SERVER_IP>
    User <CPANEL_USER>
    Port <SSH_PORT>
    IdentityFile ~/.ssh/id_ed25519
    ForwardAgent yes
    StrictHostKeyChecking accept-new
```

```bash
chmod 600 ~/.ssh/config
```

What each line does:
- **HostName/User/Port** — the connection coordinates.
- **IdentityFile** — which private key to offer.
- **ForwardAgent yes** — lets the server use your key to reach GitHub (layer 2 above).
- **StrictHostKeyChecking accept-new** — auto-accepts the server's fingerprint on first
  connect so automation doesn't hang on a yes/no prompt (still protects against changes
  later).

### 5.4 Test it

```bash
ssh africhart 'whoami; hostname; pwd'
```

If you get a passphrase prompt, that's expected the first time — see the next section to make
it non-interactive. If you get **`Permission denied (publickey)`**, the key isn't authorized
(redo 5.2).

Test layer 2 (server → GitHub) with agent forwarding:

```bash
ssh -A africhart 'ssh -T git@github.com'
# expect: "Hi <you>! You've successfully authenticated, but GitHub does not provide shell access."
```

---

## 6. The persistent-agent pattern (the key trick)

Our laptop key has a **passphrase** (good security). But automation runs each command in a
**fresh shell**, so it can't "remember" an unlocked key between calls. The fix: run a
**long-lived ssh-agent on a fixed socket path**, load the key once, and point every command
at that socket.

### 6.1 Start the agent and load the key (once)

```bash
export SSH_AUTH_SOCK=~/.ssh/agent.sock
# start an agent on a known socket if one isn't already there
ssh-add -l >/dev/null 2>&1 || { rm -f "$SSH_AUTH_SOCK"; ssh-agent -a "$SSH_AUTH_SOCK" >/dev/null; }
# load the key (you'll be asked for the passphrase here, interactively)
ssh-add ~/.ssh/id_ed25519
```

### 6.2 Loading the passphrase non-interactively (for fully unattended setup)

If a human can type the passphrase once, prefer 6.1. For unattended automation, feed the
passphrase via a throwaway askpass helper (and delete it immediately):

```bash
export SSH_AUTH_SOCK=~/.ssh/agent.sock
ssh-add -l >/dev/null 2>&1 || { rm -f "$SSH_AUTH_SOCK"; ssh-agent -a "$SSH_AUTH_SOCK" >/dev/null; }
AP=$(mktemp)
printf '#!/bin/sh\necho "<PASSPHRASE>"\n' > "$AP" && chmod +x "$AP"
SSH_ASKPASS="$AP" SSH_ASKPASS_REQUIRE=force setsid -w ssh-add ~/.ssh/id_ed25519 </dev/null
shred -u "$AP" 2>/dev/null || rm -f "$AP"
```

> Putting a passphrase in a script — even briefly — is a tradeoff. Do it only on a machine
> you trust, and remove the helper right after (the `shred -u` above). An alternative is a
> **dedicated passphrase-less deploy key** used *only* for this server (see §8).

### 6.3 Every subsequent command

Because each automated shell is fresh, **export the socket first**, then run anything:

```bash
export SSH_AUTH_SOCK=~/.ssh/agent.sock
ssh -A africhart 'cd ~/africhart.mgbah.dev && git pull'
```

The agent process keeps running in the background, so the passphrase is only ever entered
once (until reboot or the agent is killed).

---

## 7. Using it day to day

Always start with `export SSH_AUTH_SOCK=~/.ssh/agent.sock` in an automated shell.

**Run a single command:**
```bash
ssh africhart 'cd ~/africhart.mgbah.dev && git status'
```

**Run a multi-line script remotely** (heredoc — quote `'bash -s'`-style with `<<'REMOTE'`
so the laptop doesn't expand variables meant for the server):
```bash
ssh -A africhart 'bash -s' <<'REMOTE'
cd ~/africhart.mgbah.dev
git fetch
git log --oneline -1
REMOTE
```

**Copy files up** (efficient, resumable):
```bash
rsync -az -e ssh ./public/build/ africhart:~/africhart.mgbah.dev/public/build/
```

**Anything that needs GitHub on the server** → add `-A` (agent forwarding):
```bash
ssh -A africhart 'cd ~/africhart.mgbah.dev && git pull'
```

> This is exactly the channel used by the deployment runbook. Server-specific quirks
> (CloudLinux PHP, shipping `vendor/` because Composer can't run on the host, etc.) are
> documented separately in the deploy notes — this file is only about *the connection*.

---

## 8. Setting it up for another server or person

### Another server (e.g. a second cPanel)

SSH handles many servers cleanly — just add another `Host` block. The **same laptop public
key** can be authorized on as many servers as you like.

```sshconfig
Host cpanel2
    HostName <SERVER2_IP>
    User <CPANEL2_USER>
    Port <SERVER2_PORT>
    IdentityFile ~/.ssh/id_ed25519
    ForwardAgent yes
    StrictHostKeyChecking accept-new
```

Then run the §5.2 `authorized_keys` step in **that** server's Terminal. Now `ssh cpanel2 …`
works alongside `ssh africhart …` with zero interference.

### Another person (a teammate)

Each person uses **their own** key — never share private keys. For a teammate to get the same
capability:

1. They generate their own key (`ssh-keygen -t ed25519`).
2. They send you their **public** key (`~/.ssh/id_ed25519.pub`).
3. You append it to the server's `~/.ssh/authorized_keys` (one line per person).
4. They create their own `~/.ssh/config` alias (§5.3) and use their own agent (§6).

To authenticate the server → GitHub for them, their key must be on a GitHub account with repo
access, and they connect with `ssh -A`.

### Dedicated deploy key (cleaner for unattended CI/automation)

Instead of forwarding a personal, passphrase-locked key, create a **passphrase-less key used
only for this server**, so automation never needs a passphrase or an askpass hack:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/africhart_deploy -N "" -C "africhart-deploy"
# authorize africhart_deploy.pub on the server (§5.2)
# point the alias's IdentityFile at ~/.ssh/africhart_deploy and add: IdentitiesOnly yes
```

Tradeoff: a key with no passphrase sits on disk. Scope it to one server, and you can delete
it anytime (§11). For server→GitHub with a deploy key, register that key as a **Deploy Key**
on the GitHub repo (Settings → Deploy keys) instead of forwarding.

---

## 9. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `Permission denied (publickey)` | Laptop key not in server `authorized_keys` | Redo §5.2; ensure you pasted the **public** key and perms are `700 ~/.ssh`, `600 authorized_keys` |
| Hangs / repeatedly asks for passphrase in automation | Agent not running or `SSH_AUTH_SOCK` not exported | `export SSH_AUTH_SOCK=~/.ssh/agent.sock` then `ssh-add -l`; reload key (§6) |
| `ssh_askpass: exec(...): No such file` then denied | No key in agent → SSH fell back to interactive password | Load the key into the agent (§6.1/6.2) |
| Server `git pull` → `Please make sure you have the correct access rights` | No agent forwarding, or laptop key not on GitHub | Use `ssh -A`; confirm `ForwardAgent yes`; verify `ssh -A africhart 'ssh -T git@github.com'` |
| `Connection refused` / timeout | Wrong SSH port | Re-probe ports (§5.1); check cPanel SSH Access |
| `Host key verification failed` | Server fingerprint changed/unknown | First time: rely on `accept-new`; if it legitimately changed, remove the old line: `ssh-keygen -R '[<SERVER_IP>]:<SSH_PORT>'` |
| Agent gone after reboot | ssh-agent is per-session | Re-run §6.1 (one passphrase entry) |

Useful checks:
```bash
ssh -v africhart 'true'                 # verbose: see which key is offered and why auth fails
echo "$SSH_AUTH_SOCK"; ssh-add -l       # is the agent socket set and the key loaded?
```

---

## 10. Security model & hardening

- **Private keys never leave the laptop.** Only `.pub` files are copied to servers.
- **Passphrase on the key** protects it at rest; the agent unlocks it in memory only.
- **Agent forwarding (`-A`) is powerful** — while connected, a root-level actor on the server
  could use your forwarded key. Only forward to servers you trust, and only when you need the
  server to reach GitHub. Don't leave forwarded sessions open indefinitely.
- **Prefer a scoped deploy key** (§8) for unattended automation over forwarding a personal
  key.
- **Never commit** real host/IP/username/port/passphrase or any `.env`/credentials. This doc
  is safe to commit *because* it uses placeholders.
- **`StrictHostKeyChecking accept-new`** trusts the host on first sight (fine for a host you
  control) but still alarms if the fingerprint later changes.
- Consider restricting the key on the server side with `authorized_keys` options
  (e.g. `from="<your-ip>" ssh-ed25519 ...`) if you have a static IP.

---

## 11. Revoking access (teardown)

To remove a machine's or person's access:

- **On the server:** delete their line from `~/.ssh/authorized_keys`.
- **On the laptop (optional):** remove the alias from `~/.ssh/config`; delete a dedicated
  deploy key (`rm ~/.ssh/africhart_deploy*`); clear the agent (`ssh-add -D`) or kill it
  (`ssh-agent -k` / remove `~/.ssh/agent.sock`).
- **GitHub:** if you registered a deploy key, remove it under the repo's **Settings → Deploy
  keys**; or remove the personal key under your GitHub **account → SSH keys**.

---

## 12. Quick reference

```bash
# one-time per machine session: bring the agent up with your key
export SSH_AUTH_SOCK=~/.ssh/agent.sock
ssh-add -l >/dev/null 2>&1 || { rm -f "$SSH_AUTH_SOCK"; ssh-agent -a "$SSH_AUTH_SOCK" >/dev/null; }
ssh-add ~/.ssh/id_ed25519            # type passphrase once

# everyday use (export the socket first in any fresh shell)
export SSH_AUTH_SOCK=~/.ssh/agent.sock
ssh africhart 'cd ~/africhart.mgbah.dev && git status'      # run a command
ssh -A africhart 'cd ~/africhart.mgbah.dev && git pull'     # + GitHub access
rsync -az -e ssh ./public/build/ africhart:~/africhart.mgbah.dev/public/build/   # upload

# health checks
ssh africhart 'whoami; hostname'
ssh -A africhart 'ssh -T git@github.com'
```

**Our connection facts** (keep the real ones in a private, uncommitted note — not here):
`Host alias: africhart` · `HostName: <SERVER_IP>` · `Port: <SSH_PORT>` ·
`User: <CPANEL_USER>` · `Key: ~/.ssh/id_ed25519` (passphrase-protected).

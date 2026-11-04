// israelrocklyn.info: progressive enhancement only. Every page works without this file.
(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const data = JSON.parse($("#site-data")?.textContent || "{}");
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  /* ------------------------------------------------------------ theme */
  $$("[data-theme-toggle]").forEach((btn) =>
    btn.addEventListener("click", () => {
      const next = document.documentElement.dataset.theme === "light" ? "dark" : "light";
      document.documentElement.dataset.theme = next;
      document.cookie = `theme=${next}; path=/; max-age=31536000; SameSite=Lax; Secure`;
    }),
  );

  /* ------------------------------------------------------------ mobile menu */
  const toggle = $(".menu-toggle");
  const nav = $("#site-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", () => {
      const open = nav.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(open));
    });
    nav.addEventListener("click", (e) => {
      if (e.target instanceof HTMLAnchorElement) {
        nav.classList.remove("is-open");
        toggle.setAttribute("aria-expanded", "false");
      }
    });
  }

  /* ------------------------------------------------------------ section rail */
  const railLinks = $$("[data-rail]");
  if (railLinks.length && "IntersectionObserver" in window) {
    const byId = Object.fromEntries(railLinks.map((a) => [a.dataset.rail, a]));
    const io = new IntersectionObserver(
      (entries) => entries.forEach((en) => {
        if (en.isIntersecting) {
          railLinks.forEach((a) => a.classList.remove("is-active"));
          byId[en.target.id]?.classList.add("is-active");
        }
      }),
      { rootMargin: "-45% 0px -50% 0px" },
    );
    $$("[data-section]").forEach((s) => io.observe(s));
  }

  /* ------------------------------------------------------------ trace animation */
  const trace = $("[data-trace]");
  if (trace && !reduceMotion && "IntersectionObserver" in window) {
    $$(".trace-hop", trace).forEach((li, i) => li.style.setProperty("--i", String(i)));
    const packet = document.createElement("span");
    packet.className = "packet";
    packet.setAttribute("aria-hidden", "true");
    $(".trace-list", trace).append(packet);
    trace.classList.add("is-armed");
    new IntersectionObserver((entries, obs) => {
      if (entries.some((e) => e.isIntersecting)) {
        trace.classList.add("is-live");
        obs.disconnect();
      }
    }, { threshold: 0.25 }).observe(trace);
  }

  /* ------------------------------------------------------------ portrait tilt */
  const stage = $("[data-portrait]");
  if (stage && !reduceMotion && window.matchMedia("(pointer: fine)").matches) {
    const hero = stage.closest(".hero") || document.body;
    let frame = 0;
    hero.addEventListener("pointermove", (ev) => {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(() => {
        const r = stage.getBoundingClientRect();
        // -1..1 from the portrait's centre, clamped so far-away pointers don't over-rotate.
        const x = Math.max(-1, Math.min(1, (ev.clientX - r.left - r.width / 2) / r.width));
        const y = Math.max(-1, Math.min(1, (ev.clientY - r.top - r.height / 2) / r.height));
        stage.style.setProperty("--ry", `${(x * 7).toFixed(2)}deg`);
        stage.style.setProperty("--rx", `${(-y * 5).toFixed(2)}deg`);
        stage.style.setProperty("--px", `${(x * 8).toFixed(1)}px`);
        stage.style.setProperty("--py", `${(y * 6).toFixed(1)}px`);
      });
    });
    hero.addEventListener("pointerleave", () => {
      ["--rx", "--ry", "--px", "--py"].forEach((k) => stage.style.removeProperty(k));
    });
  }

  /* ------------------------------------------------------------ count-up stats */
  const counters = $("[data-count]");
  if (counters && !reduceMotion && "IntersectionObserver" in window) {
    const nums = $$("strong", counters).map((el) => {
      const m = el.textContent.match(/^(\d+)(.*)$/);
      return m && Number(m[1]) > 0 ? { el, to: Number(m[1]), suffix: m[2] } : null;
    }).filter(Boolean);
    new IntersectionObserver((entries, obs) => {
      if (!entries.some((e) => e.isIntersecting)) return;
      obs.disconnect();
      const start = performance.now();
      const tick = (now) => {
        const t = Math.min(1, (now - start) / 1200);
        const eased = 1 - Math.pow(1 - t, 3);
        nums.forEach(({ el, to, suffix }) => { el.textContent = Math.round(to * eased) + suffix; });
        if (t < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    }, { threshold: 0.4 }).observe(counters);
  }

  /* ------------------------------------------------------------ project filters */
  const chips = $$(".chip[data-filter]");
  const cards = $$(".card[data-area]");
  chips.forEach((chip) =>
    chip.addEventListener("click", () => {
      const area = chip.dataset.filter;
      chips.forEach((c) => c.setAttribute("aria-pressed", String(c === chip)));
      cards.forEach((card) => { card.hidden = area !== "all" && card.dataset.area !== area; });
    }),
  );

  /* ------------------------------------------------------------ print (résumé) */
  $$("[data-print]").forEach((b) => b.addEventListener("click", () => window.print()));

  /* ------------------------------------------------------------ command palette */
  const palette = $(".palette");
  if (palette && typeof palette.showModal === "function") {
    const input = $("#palette-input");
    const list = $(".palette-list");
    const items = [
      ...["top:Intro", "trace:Trace a request", "work:Selected work", "capabilities:Capabilities", "notes:Engineering notes",
        "shell:Shell", "services:Web & growth", "contact:Contact"].map((s) => {
        const [id, label] = s.split(":");
        return { label, hint: "section", href: `/#${id}` };
      }),
      ...(data.projects || []).map((p) => ({ label: p.title, hint: "project", href: `/work/${p.slug}`, extra: `${p.tagline} ${p.stack.join(" ")}` })),
      ...(data.notes || []).map((n) => ({ label: n.title, hint: "note", href: `/notes/${n.slug}` })),
      { label: "Résumé (print or PDF)", hint: "page", href: "/resume" },
      { label: "GitHub profile", hint: "link", href: data.github },
      { label: "Switch colour theme", hint: "action", run: () => $("[data-theme-toggle]")?.click() },
    ];
    let shown = [];
    let sel = 0;
    const render = () => {
      const q = input.value.trim().toLowerCase();
      shown = items.filter((it) => !q || `${it.label} ${it.hint} ${it.extra || ""}`.toLowerCase().includes(q)).slice(0, 12);
      sel = Math.min(sel, Math.max(0, shown.length - 1));
      list.replaceChildren(...shown.map((it, i) => {
        const li = document.createElement("li");
        li.setAttribute("role", "option");
        li.setAttribute("aria-selected", String(i === sel));
        li.append(document.createTextNode(it.label));
        const small = document.createElement("small");
        small.textContent = it.hint;
        li.append(small);
        li.addEventListener("click", () => go(it));
        li.addEventListener("mousemove", () => { if (sel !== i) { sel = i; render(); } });
        return li;
      }));
    };
    const go = (it) => {
      palette.close();
      if (it.run) it.run();
      else if (it.href) window.location.href = it.href;
    };
    const open = () => { input.value = ""; sel = 0; render(); palette.showModal(); input.focus(); };
    $$("[data-palette-open]").forEach((b) => b.addEventListener("click", open));
    document.addEventListener("keydown", (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") { e.preventDefault(); palette.open ? palette.close() : open(); }
    });
    input.addEventListener("input", () => { sel = 0; render(); });
    input.addEventListener("keydown", (e) => {
      if (e.key === "ArrowDown") { e.preventDefault(); sel = (sel + 1) % Math.max(1, shown.length); render(); }
      if (e.key === "ArrowUp") { e.preventDefault(); sel = (sel - 1 + shown.length) % Math.max(1, shown.length); render(); }
      if (e.key === "Enter") { e.preventDefault(); if (shown[sel]) go(shown[sel]); }
    });
    palette.addEventListener("click", (e) => { if (e.target === palette) palette.close(); });
  }

  /* ------------------------------------------------------------ shell */
  const shell = $("[data-shell]");
  if (shell) {
    const out = $(".terminal-out", shell);
    const form = $(".terminal-line", shell);
    const input = $("#shell-input");
    const body = $(".terminal-body", shell);
    const history = [];
    let hIndex = 0;
    const projects = data.projects || [];
    const pad = (s, n) => s + " ".repeat(Math.max(1, n - s.length));

    const print = (text, cls) => {
      const p = document.createElement("pre");
      if (cls) p.className = cls;
      p.textContent = text;
      out.append(p);
    };
    const printLink = (label, href) => {
      const p = document.createElement("p");
      const a = document.createElement("a");
      a.href = href;
      a.textContent = label;
      p.append(a);
      out.append(p);
    };
    const find = (arg) => projects.find((p) => p.slug === arg || p.slug.startsWith(arg) || p.hop.host === arg);

    const commands = {
      help: () => print([
        "Commands:",
        "  whoami            who you're talking to",
        "  ls                list the projects",
        "  cat <project>     results and stack for one project",
        "  open <project>    open its case study",
        "  trace             trace a request through the stack",
        "  skills            capabilities and tools",
        "  education         degrees",
        "  notes             engineering notes",
        "  contact           how to reach me",
        "  resume            open the printable résumé",
        "  github            open GitHub",
        "  theme             switch colour theme",
        "  clear             clear the screen",
        "Tab completes project names; ↑/↓ browse history.",
      ].join("\n")),
      whoami: () => print(`${data.name}\n${data.headline} · ${data.location}\nOpen to: ${(data.roles || []).join(", ")}`),
      ls: () => print(projects.map((p) => `${pad(p.slug, 32)}${p.results[0][0]} ${p.results[0][1]}`).join("\n")),
      cat: (arg) => {
        const p = find(arg);
        if (!p) return print(`cat: ${arg || "(missing)"}: no such project. Try: ls`, "t-err");
        print(`${p.title}\n${p.tagline}\n\n${p.results.map(([v, l]) => `  ${pad(v, 16)}${l}`).join("\n")}\n\nstack: ${p.stack.join(", ")}`);
        printLink(`→ case study /work/${p.slug}`, `/work/${p.slug}`);
      },
      open: (arg) => {
        const p = find(arg);
        if (!p) return print(`open: ${arg || "(missing)"}: no such project. Try: ls`, "t-err");
        print(`opening /work/${p.slug} …`, "t-ok");
        window.location.href = `/work/${p.slug}`;
      },
      trace: () => {
        print(`trace-request --to israelrocklyn.info (${data.projects.length} hops)`, "t-dim");
        const order = ["distributed-ai-inference", "ai-research-engine", "intelligent-document-ai", "multimodal-ai-assistant",
          "autonomous-business-platform", "fraud-detection-engine", "ai-predictive-maintenance", "aws-mlops-platform",
          "ai-developer-intelligence", "enterprise-aws-cloud-platform"];
        print(` 0  ${pad("you", 22)}0 ms`);
        order.forEach((slug, i) => {
          const p = projects.find((x) => x.slug === slug);
          if (p) setTimeout(() => { print(`${String(i + 1).padStart(2)}  ${pad(p.hop.host, 22)}${p.hop.value} ${p.hop.label}`); body.scrollTop = body.scrollHeight; }, reduceMotion ? 0 : (i + 1) * 120);
        });
      },
      skills: () => print(Object.entries(data.skills || {}).map(([g, list]) => `${g}:\n  ${list.join(", ")}`).join("\n")),
      education: () => print((data.education || []).join("\n")),
      notes: () => (data.notes || []).forEach((n) => printLink(`• ${n.title}`, `/notes/${n.slug}`)),
      contact: () => { print("Use the form below, or GitHub:"); printLink(data.github, data.github); },
      resume: () => { print("opening /resume …", "t-ok"); window.location.href = "/resume"; },
      github: () => { print(`opening ${data.github} …`, "t-ok"); window.open(data.github, "_blank", "noopener"); },
      theme: () => { $("[data-theme-toggle]")?.click(); print(`theme: ${document.documentElement.dataset.theme}`, "t-ok"); },
      clear: () => out.replaceChildren(),
      sudo: (arg) => print(arg === "hire-me" || arg === "hire" ? "Permission granted. Scroll to Contact, or type: contact" : "sudo: nice try. Try: sudo hire-me", "t-ok"),
      date: () => print(new Date().toString()),
      echo: (arg) => print(arg),
    };

    const run = (line) => {
      const [cmd, ...rest] = line.trim().split(/\s+/);
      const arg = rest.join(" ");
      print(`guest@ir:~$ ${line}`, "t-cmd");
      if (!cmd) return;
      const fn = commands[cmd.toLowerCase()];
      if (fn) fn(arg);
      else print(`${cmd}: command not found. Type 'help'.`, "t-err");
    };

    print(`Welcome. This shell reads the same data as the page.\nType 'help' to start, or try 'trace'.`, "t-dim");
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const line = input.value;
      input.value = "";
      if (line.trim()) { history.push(line); hIndex = history.length; }
      run(line);
      body.scrollTop = body.scrollHeight;
    });
    input.addEventListener("keydown", (e) => {
      if (e.key === "ArrowUp" && history.length) { e.preventDefault(); hIndex = Math.max(0, hIndex - 1); input.value = history[hIndex]; }
      if (e.key === "ArrowDown" && history.length) { e.preventDefault(); hIndex = Math.min(history.length, hIndex + 1); input.value = history[hIndex] || ""; }
      if (e.key === "Tab") {
        const [cmd, part = ""] = input.value.split(/\s+/, 2);
        const names = part !== "" || input.value.endsWith(" ") ? projects.map((p) => p.slug) : Object.keys(commands);
        const target = part !== "" || input.value.endsWith(" ") ? part : cmd;
        const hits = names.filter((n) => n.startsWith(target));
        if (hits.length) {
          e.preventDefault();
          if (hits.length === 1) input.value = part !== "" || input.value.endsWith(" ") ? `${cmd} ${hits[0]}` : `${hits[0]} `;
          else print(hits.join("  "), "t-dim");
        }
      }
    });
    body.addEventListener("click", () => { if (!window.getSelection()?.toString()) input.focus({ preventScroll: true }); });
  }

  /* ------------------------------------------------------------ demo lightbox */
  const lightbox = $(".lightbox");
  const gallery = $("[data-gallery]");
  if (lightbox && gallery && typeof lightbox.showModal === "function") {
    const links = $$("a[data-shot]", gallery);
    const figure = $("figure", lightbox);
    const caption = $("figcaption", lightbox);
    const count = $("[data-lb-count]", lightbox);
    const img = document.createElement("img");
    figure.prepend(img);
    let index = 0;
    const show = (i) => {
      index = (i + links.length) % links.length;
      const link = links[index];
      const thumb = $("img", link);
      img.src = link.href;
      img.alt = thumb?.alt || "";
      caption.textContent = thumb?.alt || "";
      count.textContent = `${index + 1} / ${links.length}`;
    };
    links.forEach((link, i) => link.addEventListener("click", (e) => { e.preventDefault(); show(i); lightbox.showModal(); }));
    lightbox.addEventListener("click", (e) => {
      const action = e.target instanceof HTMLElement ? e.target.dataset.lb : undefined;
      if (action === "prev") show(index - 1);
      else if (action === "next") show(index + 1);
      else if (action === "close" || e.target === lightbox) lightbox.close();
    });
    lightbox.addEventListener("keydown", (e) => {
      if (e.key === "ArrowLeft") show(index - 1);
      if (e.key === "ArrowRight") show(index + 1);
    });
  }

  /* ------------------------------------------------------------ copy buttons */
  $$("[data-copy]").forEach((btn) =>
    btn.addEventListener("click", async () => {
      const code = btn.parentElement?.querySelector("code")?.textContent || "";
      try {
        await navigator.clipboard.writeText(code);
        btn.textContent = "copied";
      } catch {
        btn.textContent = "select + copy";
      }
      setTimeout(() => { btn.textContent = "copy"; }, 1800);
    }),
  );

  /* ------------------------------------------------------------ contact form */
  const cform = $(".contact-form");
  if (cform) {
    const status = $(".form-status", cform);
    const button = $('button[type="submit"]', cform);
    const clearErrors = () => {
      $$(".field-error", cform).forEach((p) => { p.hidden = true; p.textContent = ""; });
      $$("[aria-invalid]", cform).forEach((el) => el.removeAttribute("aria-invalid"));
    };
    const showStatus = (text, ok) => {
      status.textContent = text;
      status.classList.toggle("is-ok", ok);
      status.classList.toggle("is-error", !ok);
    };
    cform.addEventListener("submit", async (event) => {
      event.preventDefault();
      clearErrors();
      button.disabled = true;
      showStatus("Sending…", true);
      try {
        const response = await fetch(cform.action, { method: "POST", body: new FormData(cform), headers: { Accept: "application/json" } });
        const result = await response.json();
        showStatus(result.message, result.ok);
        if (result.ok) { cform.reset(); return; }
        let first = null;
        for (const [field, message] of Object.entries(result.errors || {})) {
          const input = cform.elements.namedItem(field);
          const error = document.getElementById(`${field}-error`);
          if (input && error) {
            input.setAttribute("aria-invalid", "true");
            error.textContent = message;
            error.hidden = false;
            first ??= input;
          }
        }
        first?.focus();
      } catch {
        showStatus("Your message could not be sent. Please check your connection and try again.", false);
      } finally {
        button.disabled = false;
      }
    });
  }
})();

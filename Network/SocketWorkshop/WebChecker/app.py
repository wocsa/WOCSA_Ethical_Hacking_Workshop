from flask import Flask, request, render_template_string, jsonify
from flask import send_from_directory
import markdown
from markdown.extensions import extra, fenced_code, tables
import os

app = Flask(__name__)

# Known challenge flags
KNOWN_FLAGS = {
    "WOCSA{s0cket_pr0gramm1ng_1s_3asy}": "Challenge 1 - XOR (9001)",
    "WOCSA{s0cket_pr0gramm1ng_1s_fas7}": "Challenge 2 - Timed Math (9002)",
    "WOCSA{crazy_pla1nt3xt}": "Challenge 3 - UDP Broadcast (9999)",
    "WOCSA{D1ffi3_H3llm4n}": "Challenge 4 - Diffie-Hellman (9003)",
    "WOCSA{signed_and_true_flag}": "Challenge 5 - Signed Flag (9004)",
}

# Path to the README file (mounted from project root)
README_PATH = os.environ.get("README_PATH", "/project/README.md")

# HTML Template
TEMPLATE = """
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Socket Workshop Flag Verification Portal</title>
  <style>
    body {
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial;
        margin: 2rem;
        display: flex;
        flex-direction: column;
        align-items: center; /* center everything */
        background-color: #f5f5f5;
    }

    h1, h2 {
        color: #222;
        text-align: center;
    }

    .card {
        padding: 1rem;
        border: 1px solid #ccc;
        border-radius: 8px;
        background: #fff;
        margin-bottom: 1rem;
        width: 100%;
        max-width: 700px;
        text-align: left; /* card content left-aligned */
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    form {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    input[type="text"] {
        flex: 1;
        padding: 0.5rem;
        font-size: 1rem;
    }

    button {
        padding: 0.5rem 1rem;
        cursor: pointer;
    }

    .ok {
        color: green;
        font-weight: bold;
    }

    .bad {
        color: crimson;
        font-weight: bold;
    }

    img {
        max-width: 300px;
        height: auto;
        margin: 1rem 0;
    }
  </style>
</head>
<body>
  <h1>Socket Workshop Flag Verification Portal</h1>
  <img src="{{ url_for('static', filename='WocsaEthicalHacking.jpg') }}" alt="WOCSA Ethical Hacking Logo" />

  <div class="card">
    <h2>Verify a flag (works for all challenges)</h2>
    <form id="checkForm" method="post" action="/verify">
      <input type="text" name="flag" id="flag" placeholder="Enter your flag (e.g., WOCSA{...})" required />
      <button type="submit">Verify</button>
    </form>

    <div id="result">
      {% if result is defined %}
        {% if result.found %}
          <p class="ok">Valid flag — {{ result.challenge }}</p>
        {% else %}
          <p class="bad">Invalid flag</p>
        {% endif %}
      {% endif %}
    </div>
  </div>

  <div class="card">
    <h2>README</h2>
    <div id="readme">{{ readme_html|safe }}</div>
  </div>

  <script>
    // Submit form via fetch for smoother UX
    const form = document.getElementById('checkForm');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const flag = document.getElementById('flag').value;
      const res = await fetch('/api/verify', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({flag})
      });
      const json = await res.json();
      const out = document.getElementById('result');
      if (json.found) {
        out.innerHTML = `<p class="ok">Valid flag — ${json.challenge}</p>`;
      } else {
        out.innerHTML = `<p class="bad">Invalid flag</p>`;
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
  <script>
    mermaid.initialize({ startOnLoad: true });
  </script>

</body>
</html>
"""

def load_readme():
    """Convert README.md to HTML with extra Markdown extensions for tables and code blocks."""
    try:
        with open(README_PATH, "r", encoding="utf-8") as f:
            content = f.read()
        # Use extensions to support tables, fenced code, etc.
        return markdown.markdown(content, extensions=['fenced_code', 'tables', 'codehilite', 'toc', 'extra'])
    except FileNotFoundError:
        return "<p><em>README.md not found. Make sure it is mounted into the container.</em></p>"

@app.route("/", methods=["GET"])
def index():
    readme_html = load_readme()
    return render_template_string(TEMPLATE, readme_html=readme_html)

@app.route("/verify", methods=["POST"])
def verify_form():
    flag = (request.form.get("flag") or "").strip()
    found = flag in KNOWN_FLAGS
    return render_template_string(
        TEMPLATE,
        readme_html=load_readme(),
        result={"found": found, "challenge": KNOWN_FLAGS.get(flag)},
    )

@app.route("/api/verify", methods=["POST"])
def api_verify():
    data = request.get_json(force=True, silent=True) or {}
    flag = (data.get("flag") or "").strip()
    if not flag:
        return jsonify({"ok": False, "error": "Missing flag"}), 400
    found = flag in KNOWN_FLAGS
    return jsonify({"ok": True, "found": found, "challenge": KNOWN_FLAGS.get(flag)})


# Serve static files (image) from the same folder as app.py
@app.route("/static/<path:filename>")
def static_files(filename):
    return send_from_directory(os.path.dirname(os.path.abspath(__file__)), filename)

if __name__ == "__main__":
    host = os.environ.get("WEB_HOST", "0.0.0.0")
    port = int(os.environ.get("WEB_PORT", "8000"))
    app.run(host=host, port=port)

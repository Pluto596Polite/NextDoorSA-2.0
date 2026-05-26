# Bootstrap initialization for NextDoorSA

This folder contains a minimal web entrypoint demonstrating Bootstrap CSS integrated via the CDN.

Files:
- `index.html` — example page that loads Bootstrap CSS & JS from jsDelivr CDN and `styles.css`.
- `styles.css` — small custom overrides and starting place for your styles.

How to use
1. Open `web/index.html` in your browser (double-click or serve from a static server).
2. To use Bootstrap locally or via npm, you can initialize a package.json in this folder and install bootstrap:

   ```bash
   cd web
   npm init -y
   npm install bootstrap@5
   ```

   Then update the HTML to reference the local CSS and JS (e.g. `node_modules/bootstrap/dist/css/bootstrap.min.css`).

Notes
- The index uses CDN links for quick setup. Replace the CDN references with local files if you need offline usage or a build pipeline.
- If your project is a Spring Boot or other server app, copy `web` contents into the appropriate static resource folder (e.g. `src/main/resources/static`).


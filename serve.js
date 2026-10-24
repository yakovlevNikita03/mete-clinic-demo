/**
 * Мини-сервер для локального просмотра статического сайта.
 * Запуск: node serve.js  (или PORT=8080 node serve.js)
 * Не нужен для публикации: сайт полностью статический.
 */
const http = require("http");
const fs = require("fs");
const path = require("path");

const root = __dirname;
const port = Number(process.env.PORT || process.argv[2] || 4173);

const types = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".webp": "image/webp",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".ico": "image/x-icon",
  ".txt": "text/plain; charset=utf-8",
  ".md": "text/plain; charset=utf-8"
};

const server = http.createServer((req, res) => {
  let pathname;
  try {
    pathname = decodeURIComponent(new URL(req.url, "http://localhost").pathname);
  } catch (error) {
    res.writeHead(400).end("Bad request");
    return;
  }

  let filePath = path.join(root, pathname === "/" ? "index.html" : pathname);
  if (!filePath.startsWith(root)) {
    res.writeHead(403).end("Forbidden");
    return;
  }

  fs.stat(filePath, (statError, stats) => {
    if (!statError && stats.isDirectory()) {
      filePath = path.join(filePath, "index.html");
    }

    fs.readFile(filePath, (readError, data) => {
      if (readError) {
        res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" }).end("404 — файл не найден");
        return;
      }
      const type = types[path.extname(filePath).toLowerCase()] || "application/octet-stream";
      res.writeHead(200, { "Content-Type": type, "Cache-Control": "no-cache" });
      res.end(data);
    });
  });
});

server.listen(port, "127.0.0.1", () => {
  console.log(`METE CLINIC: http://127.0.0.1:${port}`);
});

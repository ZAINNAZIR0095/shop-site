const { app, BrowserWindow } = require('electron');
const { spawn } = require('child_process');
const http = require('http');
const path = require('path');

let phpProcess;
let npmProcess;

function isServerReady(port = 8000) {
  return new Promise((resolve) => {
    const req = http.get(`http://127.0.0.1:${port}`, () => {
      resolve(true);
    });
    req.on('error', () => {
      resolve(false);
    });
  });
}

async function waitForServer(port = 8000, maxAttempts = 30) {
  for (let i = 0; i < maxAttempts; i++) {
    if (await isServerReady(port)) {
      return true;
    }
    await new Promise((resolve) => setTimeout(resolve, 1000));
  }
  return false;
}

function startServers() {
  return new Promise((resolve, reject) => {
    const projectRoot = path.join(__dirname, '..');

    // Start PHP artisan serve
    console.log('Starting PHP artisan serve...');
    phpProcess = spawn('php', ['artisan', 'serve'], {
      cwd: projectRoot,
      stdio: 'pipe',
    });

    phpProcess.stderr.on('data', (data) => {
      console.log(`PHP: ${data}`);
    });

    // Start npm run dev
    console.log('Starting npm run dev...');
    npmProcess = spawn('npm', ['run', 'dev'], {
      cwd: projectRoot,
      stdio: 'pipe',
      shell: true,
    });

    npmProcess.stderr.on('data', (data) => {
      console.log(`NPM: ${data}`);
    });

    // Wait for servers to be ready
    setTimeout(() => {
      waitForServer(8000).then((ready) => {
        if (ready) {
          console.log('Servers are ready!');
          resolve();
        } else {
          console.warn('Servers may not be fully ready, but proceeding...');
          resolve();
        }
      });
    }, 2000);
  });
}

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
  });

  win.loadURL('http://127.0.0.1:8000');
}

app.whenReady().then(async () => {
  await startServers();
  createWindow();
});

// Clean up processes when app closes
app.on('before-quit', () => {
  if (phpProcess) phpProcess.kill();
  if (npmProcess) npmProcess.kill();
});

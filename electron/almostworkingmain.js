const { app, BrowserWindow } = require('electron')
const { spawn } = require('child_process')
const path = require('path')
const http = require('http')

let phpProcess

// Check if Laravel server is ready
function isServerReady(port = 8000) {
  return new Promise((resolve) => {
    const req = http.get(`http://127.0.0.1:${port}`, () => resolve(true))
    req.on('error', () => resolve(false))
  })
}

async function waitForServer(port = 8000, tries = 20) {
  for (let i = 0; i < tries; i++) {
    if (await isServerReady(port)) return true
    await new Promise((r) => setTimeout(r, 1000))
  }
  return false
}

// Start Laravel using portable PHP
function startLaravel() {
  return new Promise((resolve, reject) => {
    // const phpPath = path.join(__dirname, '../php/php.exe')
    const isDev = !app.isPackaged

const phpPath = isDev
  ? path.join(__dirname, '../php/php.exe')
  : path.join(process.resourcesPath, 'php/php.exe')

    // const laravelRoot = path.join(__dirname, '..')

//     const laravelRoot = isDev
//   ? path.join(__dirname, '..')
//   : process.resourcesPath
const laravelRoot = isDev
  ? path.join(__dirname, '..')
  : path.join(process.resourcesPath, 'app')

    console.log('Starting PHP server from:', phpPath)
    console.log('Laravel root:', laravelRoot)

phpProcess = spawn(
  phpPath,
  ['-S', '127.0.0.1:8000', '-t', 'public'],  // ← sometimes helps on Windows
  {
    cwd: laravelRoot,
    windowsHide: true,
    env: { ...process.env }   // ← can help with PATH issues
  }
);

// Also add exit handler
phpProcess.on('close', (code) => {
  console.log(`PHP exited with code ${code}`);
});

    phpProcess.stdout?.on('data', (data) => {
      console.log(`PHP: ${data}`)
    })

    phpProcess.stderr?.on('data', (data) => {
      console.error(`PHP Error: ${data}`)
    })

    phpProcess.on('error', (error) => {
      console.error('PHP process error:', error)
      reject(error)
    })

    // Wait for server to be ready
    waitForServer(8000, 30).then((success) => {
      if (success) {
        console.log('Laravel server is ready!')
        resolve()
      } else {
        reject(new Error('Laravel server failed to start within timeout'))
      }
    })
  })
}

function createWindow() {
  const win = new BrowserWindow({
    width: 1200,
    height: 800,
    show: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true
    }
  })

  // Load Laravel (Vue build is inside public/)
  win.loadURL('http://127.0.0.1:8000').catch((error) => {
    console.error('Failed to load URL:', error)
  })

  // Show window once it's ready
  win.once('ready-to-show', () => {
    console.log('Window ready to show')
    win.show()
  })

  // Handle loading finished
  win.webContents.on('did-finish-load', () => {
    console.log('Page loaded successfully')
  })

  // Handle loading failed
  win.webContents.on('crashed', () => {
    console.error('Window crashed')
  })

  // Open DevTools for debugging (remove in production)
  // win.webContents.openDevTools()

  return win
}

app.whenReady().then(async () => {
  try {
    console.log('App is ready, starting Laravel...')
    await startLaravel()
    console.log('Laravel started successfully, creating window...')
    createWindow()
  } catch (error) {
    console.error('Failed to start Laravel:', error)
    // Show error dialog to user
    const { dialog } = require('electron')
    dialog.showErrorBox(
      'Failed to Start',
      `Could not start the application: ${error.message}\n\nMake sure PHP is installed and accessible.`
    )
    process.exit(1)
  }
})

// Quit when all windows are closed
app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit()
  }
})

app.on('before-quit', () => {
  if (phpProcess) phpProcess.kill()
})

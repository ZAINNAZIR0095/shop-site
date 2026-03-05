const { app, BrowserWindow, dialog } = require('electron')
const { spawn } = require('child_process')
const path = require('path')
const fs = require('fs')
const http = require('http')

let phpProcess = null
let mainWindow = null

// Check if server is ready
function isServerReady(port = 8000) {
  return new Promise((resolve) => {
    const req = http.get(`http://127.0.0.1:${port}`, (res) => {
      resolve(true)
    })
    req.on('error', () => resolve(false))
    req.setTimeout(2000, () => {
      req.abort()
      resolve(false)
    })
  })
}

async function waitForServer(port = 8000, maxAttempts = 30) {
  for (let i = 0; i < maxAttempts; i++) {
    if (await isServerReady(port)) {
      console.log(`Server ready after ${i + 1} attempts`)
      return true
    }
    await new Promise(r => setTimeout(r, 1000))
  }
  return false
}

function getPaths() {
  const isDev = !app.isPackaged

  // In dev: electron is at /electron, php is at /php, laravel is at /
  // In production: everything is packaged in resources/app or resources/

  let phpPath, laravelRoot

  if (isDev) {
    // Development: electron folder is __dirname, go up then into php
    phpPath = path.join(__dirname, '..', 'php', 'php.exe')
    // Laravel root is one level up from electron
    laravelRoot = path.join(__dirname, '..')
  } else {
    // Production:
    // - app.getAppPath() = resources/app.asar (or resources/app if unpack)
    // - process.resourcesPath = resources/
    // PHP and Laravel files are copied to resources/ via build configuration
    phpPath = path.join(process.resourcesPath, 'php', 'php.exe')
    // note that electron-builder copies the Laravel code under `resources/laravel`
    laravelRoot = path.join(process.resourcesPath, 'laravel')
  }

  return { isDev, phpPath, laravelRoot }
}

async function startLaravel() {
  return new Promise(async (resolve, reject) => {
    const { isDev, phpPath, laravelRoot } = getPaths()

    console.log('=== PATH DEBUG ===')
    console.log('Is Dev:', isDev)
    console.log('App Path:', app.getAppPath())
    console.log('Resources Path:', process.resourcesPath)
    console.log('PHP Path:', phpPath)
    console.log('Laravel Root:', laravelRoot)
    console.log('PHP Exists:', fs.existsSync(phpPath))
    console.log('Laravel Exists:', fs.existsSync(laravelRoot))
    console.log('Public Exists:', fs.existsSync(path.join(laravelRoot, 'public')))
    console.log('Artisan Exists:', fs.existsSync(path.join(laravelRoot, 'artisan')))
    console.log('===================')

    // Verify PHP exists
    if (!fs.existsSync(phpPath)) {
      // Try to find PHP in alternative locations
      const altPaths = [
        path.join(process.resourcesPath, 'php', 'php.exe'),
        path.join(app.getAppPath(), '..', 'php', 'php.exe'),
        path.join(__dirname, 'php', 'php.exe'),
        'php' // Try system PATH as last resort
      ]

      let found = false
      for (const alt of altPaths) {
        console.log('Trying alternative:', alt)
        if (fs.existsSync(alt)) {
          console.log('Found PHP at:', alt)
          phpPath = alt
          found = true
          break
        }
      }

      if (!found && phpPath !== 'php') {
        reject(new Error(`PHP not found at ${phpPath}. Tried alternatives.`))
        return
      }
    }

    // Verify Laravel exists
    if (!fs.existsSync(laravelRoot)) {
      reject(new Error(`Laravel root not found at ${laravelRoot}`))
      return
    }

    if (!fs.existsSync(path.join(laravelRoot, 'public'))) {
      reject(new Error(`Laravel public folder not found at ${path.join(laravelRoot, 'public')}`))
      return
    }

    console.log('Starting PHP server...')
    console.log('Command:', phpPath, '-S', '127.0.0.1:8000', '-t', 'public')
    console.log('CWD:', laravelRoot)

    try {
      phpProcess = spawn(phpPath, ['-S', '127.0.0.1:8000', '-t', 'public'], {
        cwd: laravelRoot,
        windowsHide: true,
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '1' }
      })

      let errorOutput = ''
      let hasError = false

      phpProcess.stdout?.on('data', (data) => {
        console.log(`[PHP] ${data.toString().trim()}`)
      })

      phpProcess.stderr?.on('data', (data) => {
        const msg = data.toString().trim()
        console.error(`[PHP Error] ${msg}`)
        errorOutput += msg + '\n'
        // Don't immediately reject - some PHP warnings are non-fatal
      })

      phpProcess.on('error', (error) => {
        console.error('PHP process spawn error:', error)
        hasError = true
        reject(new Error(`Failed to start PHP: ${error.message}`))
      })

      phpProcess.on('close', (code) => {
        console.log(`PHP process exited with code ${code}`)
        if (code !== 0 && code !== null) {
          hasError = true
          reject(new Error(`PHP crashed with code ${code}. Error: ${errorOutput}`))
        }
      })

      // Wait for server to be ready
      const ready = await waitForServer(8000, 30)

      if (hasError) {
        reject(new Error(`PHP encountered errors: ${errorOutput}`))
        return
      }

      if (!ready) {
        reject(new Error(`Server failed to start within 30 seconds. Last error: ${errorOutput}`))
        return
      }

      console.log('✓ Laravel server is ready!')
      resolve()

    } catch (error) {
      reject(new Error(`Spawn failed: ${error.message}`))
    }
  })
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    show: false,
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      // Enable devTools so you can see console errors
      devTools: true
    }
  })

  // Load Laravel app
  const targetUrl = 'http://127.0.0.1:8000'
  console.log('Loading URL:', targetUrl)

  mainWindow.loadURL(targetUrl).catch((error) => {
    console.error('Failed to load URL:', error)
    dialog.showErrorBox('Loading Error', `Failed to load application: ${error.message}`)
  })

  // Show window when ready
  mainWindow.once('ready-to-show', () => {
    console.log('Window ready to show')
    mainWindow.show()
    // Open DevTools automatically so you can debug white screen issues
    mainWindow.webContents.openDevTools()
  })

  mainWindow.webContents.on('did-finish-load', () => {
    console.log('Page loaded successfully')
  })

  mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
    console.error('Failed to load:', errorCode, errorDescription)
    dialog.showErrorBox('Load Failed', `Error ${errorCode}: ${errorDescription}`)
  })

  mainWindow.on('closed', () => {
    console.log('mainWindow event: closed')
    mainWindow = null
  })

  return mainWindow
}

// App event handlers
app.whenReady().then(async () => {
  console.log('Electron app ready')

  try {
    await startLaravel()
    createWindow()
  } catch (error) {
    console.error('Fatal error:', error)

    dialog.showErrorBox(
      'Application Failed to Start',
      `Error: ${error.message}\n\n` +
      `PHP Path: ${getPaths().phpPath}\n` +
      `Exists: ${fs.existsSync(getPaths().phpPath)}\n\n` +
      `Laravel Root: ${getPaths().laravelRoot}\n` +
      `Exists: ${fs.existsSync(getPaths().laravelRoot)}`
    )

    app.quit()
  }
})

app.on('window-all-closed', () => {
  console.log('app event: window-all-closed')
  if (process.platform !== 'darwin') {
    app.quit()
  }
})

app.on('before-quit', () => {
  console.log('app event: before-quit, killing PHP process...')
  if (phpProcess) {
    phpProcess.kill()
    phpProcess = null
  }
})

// Handle crashes and additional app lifecycle
app.on('render-process-gone', (event, webContents, details) => {
  console.error('Renderer process gone:', details)
})

app.on('will-quit', () => {
  console.log('app event: will-quit')
})

app.on('quit', () => {
  console.log('app event: quit')
})

process.on('unhandledRejection', (reason) => {
  console.error('Unhandled Rejection:', reason)
})

process.on('uncaughtException', (error) => {
  console.error('Uncaught exception:', error)
  dialog.showErrorBox('Unexpected Error', error.message)
})

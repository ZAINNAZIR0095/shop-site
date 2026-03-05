const { existsSync } = require('fs')
const { spawnSync } = require('child_process')
const path = require('path')

const projectRoot = path.join(__dirname, '..')
const buildDir = path.join(projectRoot, 'public', 'build')
const force = process.env.FORCE_BUILD === '1' || process.env.FORCE_BUILD === 'true'

if (!existsSync(buildDir) || force) {
  console.log('Building frontend assets (production) — this may take a moment...')
  const res = spawnSync('npm', ['run', 'build'], { cwd: projectRoot, stdio: 'inherit', shell: true })
  if (res.error) {
    console.error('Build failed:', res.error)
    process.exit(1)
  }
  if (res.status !== 0) {
    console.error('Build process exited with code', res.status)
    process.exit(res.status || 1)
  }
  console.log('Build finished.')
} else {
  console.log('Found existing build at', buildDir)
  console.log('Skipping frontend build. Set environment variable FORCE_BUILD=1 to force a rebuild.')
}

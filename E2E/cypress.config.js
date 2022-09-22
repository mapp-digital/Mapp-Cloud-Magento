const { defineConfig } = require('cypress')

module.exports = defineConfig({
  chromeWebSecurity: false,
  viewportHeight: 1080,
  viewportWidth: 1920,
  watchForFileChanges: false,
  requestTimeout: 40000,
  defaultCommandTimeout: 40000,
  pageLoadTimeout: 80000,
  video: false,
  screenshotsFolder: '/results/screenshots',
  reporter: 'junit',
  reporterOptions: {
    mochaFile: '/results/output-[hash].xml',
    jenkinsMode: true,
  },
  e2e: {
    // We've imported your old cypress plugins here.
    // You may want to clean this up later by importing these.
    setupNodeEvents(on, config) {
      return require('./cypress/plugins/index.js')(on, config)
    },
    baseUrl: 'https://local.domain.com',
  },
})

// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })

Cypress.Commands.add(
    'testTrackRequest',
    {
        prevSubject: false,
    },
    (interceptor) => {
        return cy.wait(interceptor).then((interception) => {
            const url =interception.request.url;
            const urlSearchParams = new URLSearchParams(url);
            const isSmartpixel = /136699033798929\/wt\?p=6/.test(interception.request.url);
            return {params: Object.fromEntries(urlSearchParams.entries()), version: isSmartpixel ? '6' : '5'};
        });
    }
)

Cypress.on('uncaught:exception', (err, runnable) => {
    // returning false here prevents Cypress from
    // failing the test
    return false
  })

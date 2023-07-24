// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Homepage', () => {

    it('homepage basic datalayer', () => {
        let data;
        let gtmData;
        cy.server();
        cy.route('GET', '/mappintelligence/data/get').as('get');
        cy.visit('/', { responseTimeout: 120000, headers: { "Accept-Encoding": "gzip, deflate" } }).then(()=> {
            cy.wait('@get');
            cy.get('@get').should('have.property', 'status', 200);
            cy.get('@get').its('response').then((res) => {
                expect(res.body.config.tiId).to.equal('136699033798929');
                expect(res.body.config.tiDomain).to.equal('responder.wt-safetag.com');
                expect(res.body.config.customDomain).to.equal(null);
                expect(res.body.config.customPath).to.equal(null);
                expect(res.body.config.option).to.be.empty;
                expect(res.body.eventName).to.equal('add-to-cart');
                expect(res.body.dataLayer.blacklist).to.deep.equal([
                    'customerPasswordHash',
                    'customerRpToken',
                    'customerRpTokenCreatedAt'
                ]);
            });
        });
        cy.window()
            .then((win) => {
                data = win._ti;
                gtmData = win.dataLayer.filter(d => d?.event === 'mapp.load');
            })
            .then(() => {
                expect(data.pageName).to.equal('local.domain.com/');
                expect(data.pageTitle).to.equal('Home page');
                expect(data.contentCategory).to.equal('Cms');
                expect(data.addToCartEventName).to.equal('add-to-cart');

                expect(gtmData.length).to.equal(1);
                expect(gtmData[0].event).to.equal('mapp.load');
                expect(gtmData[0].mapp.pageName).to.equal('local.domain.com/');
                expect(gtmData[0].mapp.pageTitle).to.equal('Home page');
                expect(gtmData[0].mapp.contentCategory).to.equal('Cms');
            });
    });
});

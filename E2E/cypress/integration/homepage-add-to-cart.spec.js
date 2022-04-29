// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Add-to-Cart', () => {

    beforeEach( () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');
        cy.visit('/');
    });

    it('datalayer during add-to-cart event - simple product', () => {
        const expectationsForPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Cms');
                expect(params.cp1).to.equal('Cms');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/');
                expect(params.eid).to.match(/^2\d{18}$/);
             },
            '6': (params) => {
                expect(params.cg1).to.equal('Cms');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        }

        const expectationsForAddToCart = {
            '5': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cp1).to.not.exist;
                expect(params.fns).to.not.exist;
                expect(params.ba).to.equal('14');
                expect(params.ca1).to.equal('Gear');
                expect(params.ca2).to.equal('Collections');
                expect(params.ca3).to.equal('Push It Messenger Bag');
                expect(params.co).to.equal('45');
                expect(params.ct).to.equal('add-to-cart');
                expect(params.st).to.equal('add');
                expect(params.qn).to.equal('1');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cp1).to.not.exist;
                expect(params.fns).to.not.exist;
                expect(params.ba).to.equal('14');
                expect(params.co).to.equal('45');
                expect(params.ca1).to.equal('Gear');
                expect(params.ca2).to.equal('Collections');
                expect(params.ca3).to.equal('Push It Messenger Bag');
                expect(params.ct).to.equal('gtm-add-to-cart');
                expect(params.st).to.equal('add');
                expect(params.qn).to.equal('1');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
        }

        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        })
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        })

        cy.contains('Push It Messenger Bag').trigger('mouseover');
        cy.wait(1000);
        cy.get('form[data-product-sku="24-WB04"] button').click({force: true});
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCart[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCart[trackRequest.version](trackRequest.params);
        });
    });
});



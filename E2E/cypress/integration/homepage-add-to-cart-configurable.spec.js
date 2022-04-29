// / <reference types="Cypress" />
describe('MappIntelligencePluginTests: Add-to-Cart', () => {

    beforeEach( () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');
        cy.visit('/');
    });

    it('datalayer during add-to-cart event - configurable product', () => {

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
                expect(params.ba).to.equal('1562');
                expect(params.ca1).to.equal('Tees');
                expect(params.ca2).to.equal('Tees');
                expect(params.ca3).to.equal('Radiant Tee');
                expect(params.co).to.equal('22');
                expect(params.ct).to.equal('add-to-cart');
                expect(params.st).to.equal('add');
                expect(params.qn).to.equal('1');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cp1).to.not.exist;
                expect(params.fns).to.not.exist;
                expect(params.ba).to.equal('1562');
                expect(params.ca1).to.equal('Tees');
                expect(params.ca2).to.equal('Tees');
                expect(params.ca3).to.equal('Radiant Tee');
                expect(params.co).to.equal('22');
                expect(params.ct).to.equal('gtm-add-to-cart');
                expect(params.st).to.equal('add');
                expect(params.qn).to.equal('1');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
        }
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });

        cy.get('#option-label-size-143-item-167').click();
        cy.get('#option-label-color-93-item-50').click();
        cy.get('form[data-product-sku="WS12"] button').click({force: true});

        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCart[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCart[trackRequest.version](trackRequest.params);
        });
    });
});



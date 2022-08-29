// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Product detail', () => {

    beforeEach( () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');
    });

    it('product view basic datalayer', () => {
        const expectationsForPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('22');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('22');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        }
        let data;
        cy.server();
        cy.route({
            url: '/mappintelligence/data/get/*',
            method: 'get'
        }).as('getData');

        cy.visit('/argus-all-weather-tank.html')

        cy.wait('@getData', {timeout:30000}).then(() => {
            cy.window()
                .then((win) => {
                    data = win._ti;
                    expect(data.pageName).to.equal('local.domain.com/argus-all-weather-tank.html');
                    expect(data.pageTitle).to.equal('Argus All-Weather Tank');
                    expect(data.contentCategory).to.equal('Catalog');
                    expect(data.contentSubcategory).to.equal('Product');
                    expect(data.productName).to.equal('Argus All-Weather Tank');
                    expect(data.productId).to.equal('700');
                    expect(data.productPrice).to.equal('22');
                    expect(data.productQuantity).to.equal('1');
                    expect(data.productSku).to.equal('MT07');
                    expect(data.shoppingCartStatus).to.equal('view');
                    expect(data.productCategory).to.equal('Tanks');
                    expect(data.productSubCategory).to.equal('Eco Friendly');
                })
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
    });

    it('product view ajax add-to-cart', () => {

        const expectationsForAddtoCart = {
            '5': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cg2).to.not.exist;
                expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ct).to.equal('add-to-cart');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('66');
                expect(params.qn).to.equal('3');
                expect(params.st).to.equal('add');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cg2).to.not.exist;
                expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ct).to.equal('gtm-add-to-cart');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('66');
                expect(params.qn).to.equal('3');
                expect(params.st).to.equal('add');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        }

        cy.get('#option-label-size-143-item-166').click();
        cy.get('#option-label-color-93-item-52').click();
        cy.get('#qty').clear().type('3');
        cy.contains('Add to Cart').click().then(()=>{
            cy.testTrackRequest('@trackRequest').then(trackRequest => {
                expectationsForAddtoCart[trackRequest.version](trackRequest.params);
            });
            cy.testTrackRequest('@trackRequest').then(trackRequest => {
                expectationsForAddtoCart[trackRequest.version](trackRequest.params);
            });
        });
    });
});

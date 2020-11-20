// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Product detail', () => {

    it('product view basic datalayer', () => {
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

    });

    it('product view ajax add-to-cart', () => {
        let wts;
        cy.server();
        cy.route({
            url: '/mappintelligence/data/get/*',
            method: 'get'
        }).as('getData');

        cy.visit('/argus-all-weather-tank.html').then(()=> {
            cy.wait('@getData',{timeout:30000});
        });
        cy.window().then((win) => {
            let calls = 0;
            wts = cy.stub(win.wts, 'push', (arg) => {
                console.log('call ' + calls, arg, win._ti)

                switch (calls) {
                    case 0:
                        expect(arg[0]).to.equal('linkId');
                        expect(arg[1]).to.equal('add-to-cart');
                        break;
                    case 1:
                        expect(arg[0]).to.equal('send');
                        expect(arg[1]).to.equal('pageupdate');

                        expect(win._ti.productCategory).to.equal('Tanks');
                        expect(win._ti.productSubCategory).to.equal('Eco Friendly');
                        expect(win._ti.productCost).to.equal('66');
                        expect(win._ti.productId).to.equal('700');
                        expect(win._ti.productName).to.equal('Argus All-Weather Tank');
                        expect(win._ti.productQuantity).to.equal('3');
                        expect(win._ti.productSku).to.equal('MT07-XS-Gray');
                        expect(win._ti.addToCartEventName).to.equal('add-to-cart');
                        expect(win._ti.shoppingCartStatus).to.equal('add');
                        expect(win._ti.productAttributesColor).to.equal('Gray');
                        expect(win._ti.productAttributesSize).to.equal('XS');
                }
                calls++;
            });
        });
        cy.get('#option-label-size-143-item-166').click();
        cy.get('#option-label-color-93-item-52').click();
        cy.get('#qty').clear().type('3');
        cy.contains('Add to Cart').click().then(()=>{
            cy.wait('@getData');
            cy.window().then((win) => {
                expect(win._ti.shoppingCartStatus).to.equal('view');
            });
        });


    });
});

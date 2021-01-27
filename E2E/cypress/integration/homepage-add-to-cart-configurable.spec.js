// / <reference types="Cypress" />
describe('MappIntelligencePluginTests: Add-to-Cart', () => {

    beforeEach( () => {
        cy.visit('/');
        cy.contains('Default welcome msg!').should('be.visible');
    });

    it('datalayer during add-to-cart event - configurable product', () => {
        let wts;

        cy.get('#option-label-size-143-item-167').click();
        cy.get('#option-label-color-93-item-50').click();

        cy.get('form[data-product-sku="WS12"] button').click({force: true});
        cy.window().then((win) => {
            let calls = 0;
            wts = cy.stub(win.wts, 'push', (arg) => {
                console.log('call ' + calls, arg)
                if(calls === 0) {

                    expect(win._ti.productCategory).to.equal('Tees');
                    expect(win._ti.productSubCategory).to.equal('Tees');
                    expect(win._ti.productCost).to.equal('22');
                    expect(win._ti.productId).to.equal('1562');
                    expect(win._ti.productName).to.equal('Radiant Tee');
                    expect(win._ti.productQuantity).to.equal('1');
                    expect(win._ti.productSku).to.equal('WS12-S-Blue');
                    expect(win._ti.productAttributesColor).to.equal('Blue');
                    expect(win._ti.productAttributesSize).to.equal('S');
                    expect(win._ti.addToCartEventName).to.equal('add-to-cart');
                    expect(win._ti.shoppingCartStatus).to.equal('add');

                    expect(arg[0]).to.equal('linkId');
                    expect(arg[1]).to.equal('false');

                } else if(calls === 1) {

                    expect(arg[0]).to.equal('send');
                    expect(arg[1]).to.equal('pageupdate');
                    expect(arg[2]).to.equal(true);

                    expect(win._ti.addToCartEventName).to.equal('add-to-cart');
                    expect(win._ti.productCategory).to.equal('false');
                    expect(win._ti.productSubCategory).to.equal('false');
                    expect(win._ti.productCost).to.equal('false');
                    expect(win._ti.productId).to.equal('false');
                    expect(win._ti.productName).to.equal('false');
                    expect(win._ti.productQuantity).to.equal('false');
                    expect(win._ti.productSku).to.equal('false');
                    expect(win._ti.shoppingCartStatus).to.equal('false');
                }
                calls++;
            });
        });
        // cy.get('span.counter-number').contains('1');
        // cy.window().then((win) => {
        //     expect(win._ti.addToCartEventName).to.equal('add-to-cart');
        //     expect(win._ti.shoppingCartStatus).to.equal('false');
        // });
    });
});



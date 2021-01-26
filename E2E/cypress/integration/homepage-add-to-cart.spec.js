// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Add-to-Cart', () => {

    beforeEach( () => {
        cy.visit('/');
        cy.contains('Default welcome msg!').should('be.visible');
    });

    it('datalayer during add-to-cart event - simple product', () => {
        let wts;

        cy.get('form[data-product-sku="24-WB04"] button').click({force: true});

        cy.window().then((win) => {
            let calls = 0;
            wts = cy.stub(win.wts, 'push', (arg) => {
                console.log('call ' + calls, arg)
                if(calls === 0) {

                    expect(win._ti.productCategory).to.equal('Gear');
                    expect(win._ti.productSubCategory).to.equal('Collections');
                    expect(win._ti.productCost).to.equal('45');
                    expect(win._ti.productId).to.equal('14');
                    expect(win._ti.productName).to.equal('Push It Messenger Bag');
                    expect(win._ti.productQuantity).to.equal('1');
                    expect(win._ti.productSku).to.equal('24-WB04');
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



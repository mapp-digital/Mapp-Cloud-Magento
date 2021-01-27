// / <reference types="Cypress" />
describe('MappIntelligencePluginTests: Add-to-Cart', () => {
    it('product view ajax add-to-cart', () => {
        let data;
        cy.server();
        cy.route({
            url: '/mappintelligence/data/get/*',
            method: 'get'
        }).as('getData');

        cy.visit('/argus-all-weather-tank.html').then(()=> {
            cy.wait('@getData',{timeout:30000});
        });
        cy.get('#option-label-size-143-item-166').click();
        cy.get('#option-label-color-93-item-52').click();
        cy.get('#qty').clear().type('3');
        cy.contains('Add to Cart').click().then(()=>{
            cy.wait('@getData');
        });
        cy.visit('/customer/account/login/');
        cy.get('#email').type('roni_cost@example.com');
        cy.get('#pass').type('roni_cost3@example.com');
        cy.get('#send2').click();
        cy.contains('Welcome, Veronica Costello!', {timeout: 100000}).should('be.visible');
        cy.visit('checkout/#shipping');
        // cy.get('button.action.continue.primary', {timeout: 50000}).should('.be.visible');
        cy.get('input[name="ko_unique_1"', {timeout: 50000}).check();
        cy.get('button.action.continue.primary').click();
        cy.get('.action.primary.checkout').click();
        cy.contains('Thank you for your purchase!', {timeout: 120000}).should('be.visible');
        cy.window()
            .then((win) => {
                data = win._ti;
            })
            .then(() => {
                expect(data.contentCategory).to.equal('Checkout');
                expect(data.contentSubcategory).to.equal('Onepage');
                expect(data.currency).to.equal('EUR');
                expect(data.gender).to.equal('2');
                expect(data.orderId).to.match(/\d{9}/);
                expect(data.couponValue).to.not.exist;
                expect(data.productSoldOut).to.not.exist;
                expect(data.productCost).to.equal('66');
                expect(data.productId).to.equal('700');
                expect(data.productName).to.equal('Argus All-Weather Tank');
                expect(data.productQuantity).to.equal('3');
                expect(data.productCategories).to.deep.equal(['Tanks', 'Eco Friendly', 'Default Category']);
                expect(data.productCategory).to.equal('Tanks');
                expect(data.productSubCategory).to.equal('Eco Friendly');
                expect(data.shoppingCartStatus).to.equal('conf');
                expect(data.totalOrderValue).to.equal('71.45');
            });
    });

});



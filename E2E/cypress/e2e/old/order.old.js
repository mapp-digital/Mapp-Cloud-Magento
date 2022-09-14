// / <reference types="Cypress" />
describe('MappIntelligencePluginTests: Order', () => {
    it('view, add, login, order', () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');

        const expectationsForBeforeLogin = {
            '5': (params) => {
                expect(params.cg1).to.equal('Customer');
                expect(params.cg2).to.equal('Account');
                expect(params.cp1).to.equal('Customer');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/customer/account/login/');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Customer');
                expect(params.cg2).to.equal('Account');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/customer/account/login/');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        };

        const expectationsForAfterLogin = {
            '5': (params) => {
                expect(params.cg1).to.equal('Customer');
                expect(params.cg2).to.equal('Account');
                expect(params.cp1).to.equal('Customer');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/customer/account/');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Customer');
                expect(params.cg2).to.equal('Account');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/customer/account/');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            }
        };

        cy.visit('/customer/account/login/');
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForBeforeLogin[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForBeforeLogin[trackRequest.version](trackRequest.params);
        });

        cy.get('#email').type('roni_cost@example.com');
        cy.get('#pass').type('roni_cost3@example.com');
        cy.get('#send2').click();
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAfterLogin[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAfterLogin[trackRequest.version](trackRequest.params);
        });



        const expectationsForPIAtProduct1 = {
            '5': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                // expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('22');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                // expect(params.ca1).to.equal('Tanks');
                expect(params.ca2).to.equal('Eco Friendly');
                expect(params.ca3).to.equal('Argus All-Weather Tank');
                expect(params.ba).to.equal('700');
                expect(params.co).to.equal('22');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/argus-all-weather-tank.html');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            }
        }

        const expectationsForAddtoCartAtProduct1 = {
            '5': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cg2).to.not.exist;
                // expect(params.ca1).to.equal('Tanks');
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
                // expect(params.ca1).to.equal('Tanks');
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

        cy.visit('/argus-all-weather-tank.html');
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPIAtProduct1[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPIAtProduct1[trackRequest.version](trackRequest.params);
        });

        cy.get('#option-label-size-143-item-166').click();
        cy.get('#option-label-color-93-item-52').click();
        cy.get('#qty').clear().type('3');
        cy.contains('Add to Cart').click().then(()=>{
            cy.testTrackRequest('@trackRequest').then(trackRequest => {
                expectationsForAddtoCartAtProduct1[trackRequest.version](trackRequest.params);
            });
            cy.testTrackRequest('@trackRequest').then(trackRequest => {
                expectationsForAddtoCartAtProduct1[trackRequest.version](trackRequest.params);
            });
        });

        cy.visit('/push-it-messenger-bag.html');
        const expectationsForPIAtProduct2 = {
            '5': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                expect(params.cp1).to.equal('Catalog');
                expect(params.ca1).to.equal('Gear');
                expect(params.ca2).to.equal('Collections');
                expect(params.ca3).to.equal('Push It Messenger Bag');
                expect(params.ba).to.equal('14');
                expect(params.co).to.equal('45');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/push-it-messenger-bag.html');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Catalog');
                expect(params.cg2).to.equal('Product');
                expect(params.ca1).to.equal('Gear');
                expect(params.ca2).to.equal('Collections');
                expect(params.ca3).to.equal('Push It Messenger Bag');
                expect(params.ba).to.equal('14');
                expect(params.co).to.equal('45');
                expect(params.qn).to.equal('1');
                expect(params.st).to.equal('view');
                expect(params.fns).to.not.exist;
                expect(params.la).to.equal('en');
                expect(params.one).to.not.exist;
                expect(params.pu).to.equal('https://local.domain.com/push-it-messenger-bag.html');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            }
        }

        const expectationsForAddToCartAtProduct2 = {
            '5': (params) => {
                expect(params.cg1).to.not.exist;
                expect(params.cg2).to.not.exist;
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
                expect(params.cg2).to.not.exist;
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
            }
        }
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPIAtProduct2[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPIAtProduct2[trackRequest.version](trackRequest.params);
        });
        cy.contains('Add to Cart').click({});
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCartAtProduct2[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForAddToCartAtProduct2[trackRequest.version](trackRequest.params);
        });

        cy.visit('checkout/#shipping');
        const expectationsForCheckoutPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Checkout');
                expect(params.cp1).to.equal('Checkout');
                expect(params.fns).to.not.exist;
                expect(params.ba).to.not.exist;
                expect(params.ca1).to.not.exist;
                expect(params.ca2).to.not.exist;
                expect(params.ca3).to.not.exist;
                expect(params.co).to.not.exist;
                expect(params.ct).to.not.exist;
                expect(params.st).to.not.exist;
                expect(params.qn).to.not.exist;
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Checkout');
                expect(params.fns).to.not.exist;
                expect(params.ba).to.not.exist;
                expect(params.ca1).to.not.exist;
                expect(params.ca2).to.not.exist;
                expect(params.ca3).to.not.exist;
                expect(params.co).to.not.exist;
                expect(params.ct).to.not.exist;
                expect(params.st).to.not.exist;
                expect(params.qn).to.not.exist;
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
            }
        }
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForCheckoutPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForCheckoutPI[trackRequest.version](trackRequest.params);
        });

        const expectationsForOrderPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Checkout');
                expect(params.cg2).to.equal('Onepage');
                expect(params.cp1).to.equal('Checkout');
                expect(params.fns).to.not.exist;
                expect(params.ba).to.equal('700;14');
                expect(params.ca1).to.equal('Tanks;Gear');
                expect(params.ca2).to.equal('Eco Friendly;Collections');
                expect(params.ca3).to.equal('Argus All-Weather Tank;Push It Messenger Bag');
                expect(params.co).to.equal('66;45');
                expect(params.ct).to.not.exist;
                expect(params.st).to.equal('conf');
                expect(params.qn).to.equal('3;1');
                expect(params.cr).to.equal('EUR');
                expect(params.ov).to.equal('135.16');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
                expect(params.oi).to.match(/^[0-9]{9}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Checkout');
                expect(params.cg2).to.equal('Onepage');
                expect(params.fns).to.not.exist;
                expect(params.ba).to.equal('700;14');
                expect(params.ca1).to.equal('Tanks;Gear');
                expect(params.ca2).to.equal('Eco Friendly;Collections');
                expect(params.ca3).to.equal('Argus All-Weather Tank;Push It Messenger Bag');
                expect(params.co).to.equal('66;45');
                expect(params.ct).to.not.exist;
                expect(params.st).to.equal('conf');
                expect(params.qn).to.equal('3;1');
                expect(params.cr).to.equal('EUR');
                expect(params.ov).to.equal('135.16');
                expect(params.eid).to.match(/^2\d{18}$/);
                expect(params.cd).to.match(/^[a-z0-9]{64}$/);
                expect(params.oi).to.match(/^[0-9]{9}$/);
            }
        }

        cy.get('input[name="ko_unique_1"', {timeout: 50000}).check();
        cy.get('button.action.continue.primary').click();
        cy.get('.action.primary.checkout').click();

        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForOrderPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForOrderPI[trackRequest.version](trackRequest.params);
        });
        // let data;
        // cy.window()
        //     .then((win) => {
        //         data = win._ti;
        //     })
        //     .then(() => {
        //         expect(data.contentCategory).to.equal('Checkout');
        //         expect(data.contentSubcategory).to.equal('Onepage');
        //         expect(data.currency).to.equal('EUR');
        //         expect(data.gender).to.equal('2');
        //         expect(data.orderId).to.match(/\d{9}/);
        //         expect(data.couponValue).to.not.exist;
        //         expect(data.productSoldOut).to.not.exist;
        //         expect(data.productCost).to.equal('66');
        //         expect(data.productId).to.equal('700');
        //         expect(data.productName).to.equal('Argus All-Weather Tank');
        //         expect(data.productQuantity).to.equal('3');
        //         expect(data.productCategories).to.deep.equal(['Tanks', 'Eco Friendly', 'Default Category']);
        //         expect(data.productCategory).to.equal('Tanks');
        //         expect(data.productSubCategory).to.equal('Eco Friendly');
        //         expect(data.shoppingCartStatus).to.equal('conf');
        //         expect(data.totalOrderValue).to.equal('71.45');
        //     });
    });

});



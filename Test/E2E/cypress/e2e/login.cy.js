// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Login', () => {

    beforeEach( () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');
    });

    it('login to account', () => {

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

        cy.visit('/customer/account/login/', { responseTimeout: 120000, headers: { "Accept-Encoding": "gzip, deflate" } });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForBeforeLogin[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForBeforeLogin[trackRequest.version](trackRequest.params);
        });

        cy.get('input[name="form_key"]').then(form => {
            const form_key = form.attr("value");
            cy.visit({
                url: '/customer/account/loginPost/',
                responseTimeout: 120000,
                headers: { "Accept-Encoding": "gzip, deflate" },
                method: 'POST',
                body: {
                    form_key,
                    'login[username]': 'test@mapp.com',
                    'login[password]': 'Test1234!'
                },
            }).then(()=>{
                cy.testTrackRequest('@trackRequest').then(trackRequest => {
                    expectationsForAfterLogin[trackRequest.version](trackRequest.params);
                });
                cy.testTrackRequest('@trackRequest').then(trackRequest => {
                    expectationsForAfterLogin[trackRequest.version](trackRequest.params);
                });
            })
        })
    });
});

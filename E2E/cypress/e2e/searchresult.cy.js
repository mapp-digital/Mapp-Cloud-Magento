// / <reference types="Cypress" />

describe('MappIntelligencePluginTests: Searchresult', () => {

    beforeEach( () => {
        cy.intercept(/136699033798929\/wt\?p=/).as('trackRequest');
    });

    it('search result basic datalayer', () => {
        let data;
        const expectationsForPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Catalogsearch');
                expect(params.cg2).to.equal('Result');
                expect(params.cg20).to.equal('1');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/catalogsearch/result/?q=mapp');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Catalogsearch');
                expect(params.cg2).to.equal('Result');
                expect(params.cg20).to.equal('1');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/catalogsearch/result/?q=mapp');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        }
        cy.visit('/catalogsearch/result/?q=mapp', { responseTimeout: 120000, headers: { "Accept-Encoding": "gzip, deflate" } });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });

        cy.window()
            .then((win) => {
                data = win._ti;
            })
            .then(() => {
                expect(data.pageName).to.equal('local.domain.com/catalogsearch/result/');
                expect(data.pageTitle).to.equal('Search results for: \'mapp\'');
                expect(data.contentCategory).to.equal('Catalogsearch');
                expect(data.contentSubcategory).to.equal('Result');
                expect(data.internalSearch).to.equal('mapp');
                expect(data.pageNumber).to.equal('1');
            });
    });

    it('search result basic datalayer page 2', () => {
        let data;
        cy.visit('/catalogsearch/result/index/?p=2&q=mapp', { responseTimeout: 120000, headers: { "Accept-Encoding": "gzip, deflate" } });
        const expectationsForPI = {
            '5': (params) => {
                expect(params.cg1).to.equal('Catalogsearch');
                expect(params.cg2).to.equal('Result');
                expect(params.cg20).to.equal('2');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/catalogsearch/result/index/?p=2&q=mapp');
                expect(params.eid).to.match(/^2\d{18}$/);
            },
            '6': (params) => {
                expect(params.cg1).to.equal('Catalogsearch');
                expect(params.cg2).to.equal('Result');
                expect(params.cg20).to.equal('2');
                expect(params.fns).to.equal('1');
                expect(params.la).to.equal('en');
                expect(params.one).to.equal('1');
                expect(params.pu).to.equal('https://local.domain.com/catalogsearch/result/index/?p=2&q=mapp');
                expect(params.eid).to.match(/^2\d{18}$/);
            }
        }
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
        cy.testTrackRequest('@trackRequest').then(trackRequest => {
            expectationsForPI[trackRequest.version](trackRequest.params);
        });
        cy.window()
            .then((win) => {
                data = win._ti;
            })
            .then(() => {
                expect(data.pageName).to.equal('local.domain.com/catalogsearch/result/index/');
                expect(data.pageTitle).to.equal('Search results for: \'mapp\'');
                expect(data.contentCategory).to.equal('Catalogsearch');
                expect(data.contentSubcategory).to.equal('Result');
                expect(data.internalSearch).to.equal('mapp');
                expect(data.pageNumber).to.equal('2');
            });
    });
});

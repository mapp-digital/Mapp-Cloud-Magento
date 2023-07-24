// / <reference types="Cypress" />

describe("Bundle Product", () => {
  beforeEach(() => {
    cy.intercept(/136699033798929\/wt\?p=/).as("trackRequest");
  });

  it("Bundle product - view and add-to-cart", () => {
    const expectationsForPI = {
      5: (params) => {
        expect(params.cg1).to.equal("Catalog");
        expect(params.cg2).to.equal("Product");
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("0");
        expect(params.qn).to.equal("1");
        expect(params.st).to.equal("view");
        expect(params.fns).to.equal("1");
        expect(params.la).to.equal("en");
        expect(params.one).to.equal("1");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
      6: (params) => {
        expect(params.cg1).to.equal("Catalog");
        expect(params.cg2).to.equal("Product");
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("0");
        expect(params.qn).to.equal("1");
        expect(params.st).to.equal("view");
        expect(params.fns).to.equal("1");
        expect(params.la).to.equal("en");
        expect(params.one).to.equal("1");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
    };
    let data;
    cy.server();
    cy.route({
      url: "/mappintelligence/data/get/*",
      method: "get",
    }).as("getData");

    cy.visit("/mapp-bundle.html", { responseTimeout: 120000, headers: { "Accept-Encoding": "gzip, deflate" } });

    cy.wait("@getData", { timeout: 30000 }).then(() => {
      cy.window().then((win) => {
        data = win._ti;
        expect(data.pageName).to.equal("local.domain.com/mapp-bundle.html");
        expect(data.pageTitle).to.equal("Mapp Bundle");
        expect(data.contentCategory).to.equal("Catalog");
        expect(data.contentSubcategory).to.equal("Product");
        expect(data.productName).to.equal("Mapp Bundle");
        expect(data.productId).to.equal("8");
        expect(data.productPrice).to.equal("0");
        expect(data.productQuantity).to.equal("1");
        expect(data.productSku).to.equal("mappbundle");
        expect(data.shoppingCartStatus).to.equal("view");
        expect(data.productCategory).to.equal("MAPP main category");
        expect(data.productSubCategory).to.equal("MAPP sub category");
      });
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForPI[trackRequest.version](trackRequest.params);
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForPI[trackRequest.version](trackRequest.params);
    });

    cy.get('div.product-add-form')
        .invoke('attr', 'style', 'display: block')

    cy.get("#bundle-option-1").select("Mapp Bundle Item 2 +€99.00");
    cy.get("#product-addtocart-button").click();

    const expectationsForAddToCartEvent = {
      5: (params) => {
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("99");
        expect(params.qn).to.equal("1");
        expect(params.st).to.equal("add");
        expect(params.la).to.equal("en");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
      6: (params) => {
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("99");
        expect(params.qn).to.equal("1");
        expect(params.st).to.equal("add");
        expect(params.la).to.equal("en");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
    };

    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForAddToCartEvent[trackRequest.version](trackRequest.params);
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForAddToCartEvent[trackRequest.version](trackRequest.params);
    });

    cy.get("#qty").clear().type("11");
    cy.get("#product-addtocart-button").click();

    const expectationsForMultipleAddToCartEvent = {
      5: (params) => {
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("1089");
        expect(params.qn).to.equal("11");
        expect(params.st).to.equal("add");
        expect(params.la).to.equal("en");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
      6: (params) => {
        expect(params.ca1).to.equal("MAPP main category");
        expect(params.ca2).to.equal("MAPP sub category");
        expect(params.ca3).to.equal("Mapp Bundle");
        expect(params.ba).to.equal("8");
        expect(params.co).to.equal("1089");
        expect(params.qn).to.equal("11");
        expect(params.st).to.equal("add");
        expect(params.la).to.equal("en");
        expect(params.pu).to.equal("https://local.domain.com/mapp-bundle.html");
        expect(params.eid).to.match(/^2\d{18}$/);
      },
    };

    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForMultipleAddToCartEvent[trackRequest.version](
        trackRequest.params
      );
    });
    cy.testTrackRequest("@trackRequest").then((trackRequest) => {
      expectationsForMultipleAddToCartEvent[trackRequest.version](
        trackRequest.params
      );
    });
  });
});

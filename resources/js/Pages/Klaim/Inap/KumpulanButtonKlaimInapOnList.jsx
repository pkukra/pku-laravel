import React, { useState } from "react";
import { Row, Col, Button } from "antd";

import SEP from "./SEP";
import Resume from "./Resume";
import SPRI from "./SPRI";
import Triase from "./Triase";
import LabRadiologi from "./LabRadiologi";
import Kwitansi from "./Kwitansi";
import LaporanOperasi from "./LaporanOperasi";
import Anastesi from "./Anastesi";
import PenunjangLain from "./PenunjangLain";
import LaporanVK from "./LaporanVK";
import EKlaim from "./EKlaim";

export default function Index({ no_sep, nomer_rm, kode_reg }) {
    return (
        <>
            <Row gutter={[8, 8]} style={{ width: 450 }}>
                <Col span={6}>
                    <SEP kode_reg={kode_reg} />
                    <Resume kode_reg={kode_reg} />
                    <SPRI kode_reg={kode_reg} nomer_rm={nomer_rm} />
                    <Triase kode_reg={kode_reg} nomer_rm={nomer_rm} />
                </Col>

                <Col span={6}>
                    <LaporanOperasi kode_reg={kode_reg} />
                    <Anastesi kode_reg={kode_reg} />
                    <LaporanVK kode_reg={kode_reg} />
                </Col>

                <Col span={6}>
                    <LabRadiologi kode_reg={kode_reg} nomer_rm={nomer_rm} />
                    <PenunjangLain kode_reg={kode_reg} />
                </Col>

                <Col span={6}>
                    <Kwitansi kode_reg={kode_reg} />
                    <EKlaim no_sep={no_sep} />
                </Col>
            </Row>
        </>
    );
}
